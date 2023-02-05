<?php

declare(strict_types = 1);

namespace Fayela\Http;

use Fayela\Http\Exception\BadRequestHttpException;

class DownloadBinaryFileResponse
{
    public function __construct(
        protected string $filepath,
        protected string $filename,
        protected string $currentHttpRangeHeader = ''
    ) {
    }

    public function send(): never
    {
        $filesize = (int)filesize($this->filepath);

        $seekStart = 0;
        $bufferTotalSize = $filesize;
        if (mb_strlen($this->currentHttpRangeHeader) > 6) {
            $range = array_map(
                'intval',
                explode(
                    '-',
                    substr($this->currentHttpRangeHeader, 6)
                )
            );
            $seekStart = max($range[0] ?? 0, 0);
            $seekEnd = max($range[1] ?? ($filesize - 1), 0);
            $bufferTotalSize = $seekEnd + 1 - $seekStart;

            header('HTTP/1.1 206 Partial Content');
            header(sprintf('Content-Range: bytes %s-%s/%s', $seekStart, $seekEnd, $filesize));
        } else {
            header('HTTP/1.1 404 Not found');
        }
        header(sprintf('Content-Length: %s', $filesize));
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header(sprintf('Content-disposition: attachment; filename="%s"', $this->filename));
        header('Accept-Ranges: bytes');

        $in = fopen($this->filepath, 'rb');
        if (false === $in) {
            throw new BadRequestHttpException(sprintf('unabled to open source stream %s', $this->filepath));
        }
        $out = fopen('php://output', 'wb');
        if (false === $out) {
            throw new BadRequestHttpException('unabled to open php://output');
        }

        // read chunk size
        $chunkSize = 8 * 1024;
        fseek($in, $seekStart);
        while ($bufferTotalSize > 0 && !feof($in)) {
            $read = ($bufferTotalSize > $chunkSize) ? $chunkSize : $bufferTotalSize;
            $bufferTotalSize -= $read;

            stream_copy_to_stream($in, $out, $read);
            if (1 === connection_aborted()) {
                break;
            }
        }

        fclose($in);
        fclose($out);

        exit;
    }
}
