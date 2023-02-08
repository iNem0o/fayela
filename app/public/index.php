<?php

declare(strict_types = 1);

use Fayela\Fayela;
use Fayela\Helper\HTMLGeneratorHelper;
use Fayela\Http\DownloadBinaryFileResponse;
use Fayela\Http\Exception\AbstractHttpException;
use Fayela\Http\Exception\NotFoundHttpException;
use Fayela\Http\Response;
use Fayela\Http\StatusCode;

try {
    /** @var Fayela $app */
    $app = require __DIR__ . '/../bootstrap.php';

    $htmlGenerator = new HTMLGeneratorHelper();

    $app->loadDatabase($_SERVER['REQUEST_URI'] ?? '');

    $currentDirectory = $app->getCurrentDirectory();
    if (null === $currentDirectory) {
        throw new NotFoundHttpException();
    }

    $currentFile = $app->getCurrentFile();

    if (null !== $currentFile) {
        if (!file_exists($currentFile['path']) || !is_readable($currentFile['path'])) {
            throw new NotFoundHttpException();
        }
        $response = new DownloadBinaryFileResponse(
            $currentFile['path'],
            $currentFile['name'],
            $_SERVER['HTTP_RANGE'] ?? ''
        );
        $response->send();
    }


    $defaultSortBy = null;
    $defaultSortWay = null;

    $currentSortBy = in_array($_GET['sortBy'] ?? null, $app->getConfig('allowed_sort_by'), true) ? $_GET['sortBy'] : $defaultSortBy;
    $currentSortWay = in_array($_GET['sortWay'] ?? null, $app->getConfig('allowed_sort_way'), true) ? $_GET['sortWay'] : $defaultSortWay;

    $currentItemChildren = $app->getDirectoryChildren($currentDirectory, $currentSortBy, $currentSortWay);
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title><?= $app->getConfigString('instance_name') ?></title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            <?php readfile(__DIR__ . '/../assets/style.css') ?>
        </style>
    </head>
    <body>

    <?php
    readfile(__DIR__ . '/../assets/svg-icons-definition.html') ?>

    <div class="fayela--header--server">
        <a class="fayela--header--server--name" href="/"><?= mb_strtoupper($app->getConfigString('instance_name')) ?></a>
        <div class="fayela--header--server--threads">
            <?php
            foreach ($app->getDownloadThreads() as $k => $thread) {
                echo sprintf(
                    '<span class="fayela--header--server--threads--thread fayela--header--server--threads--thread--state-%s">%s</span>',
                    mb_strtolower($thread['state']),
                    ' '
                );
            }
    ?>
        </div>
    </div>
    <div class="fayela--header--breadcrumb">
        <span class="fayela--header--breadcrumb--links">
            <?= $htmlGenerator->getBreadcrumb($app->getCurrentBreadcrumb($currentSortBy, $currentSortWay)) ?>
        </span>
        <span class="fayela--header--breadcrumb--folderstats">
        <span>
            <?= $htmlGenerator->getIconSVG('folder') ?>
            <b><?= $currentDirectory['children_total_folders'] ?? 0 ?></b>
        </span>
        |
        <span>
            <?= $htmlGenerator->getIconSVG('file') ?>
            <b><?= $currentDirectory['children_total_files'] ?? 0 ?></b>
        </span>
        |
        <span>
            <strong><?= $currentDirectory['size_human'] ?></strong>
        </span>
    </span>
    </div>
    <div class="fayela--filelist">
        <span class="fayela--filelist--row--header">
            <?= $htmlGenerator->generateSortLink('name', 'Name', $currentSortBy, $currentSortWay) ?>
            <span>&nbsp;</span>
            <?= $htmlGenerator->generateSortLink('size', 'Size', $currentSortBy, $currentSortWay, true) ?>
            <?= $htmlGenerator->generateSortLink('timestamp_create', 'Created at', $currentSortBy, $currentSortWay, true) ?>
        </span>
        <?php

        if (false === $currentDirectory['isRoot']) {
            echo $htmlGenerator->generateFilelistParentDirectoryRow();
        }

        foreach ($currentItemChildren as $item) {
            echo $htmlGenerator->generateFilelistRow($item, $app->getDirectoryPersonalization($item['path']));
        }
    ?>
    </div>


    <?= $htmlGenerator->generateRecentFilesColumns(
        array_filter($currentItemChildren, static fn ($item) => true === $item['isDir'])
    ) ?>

    <script type="text/javascript">
        <?php readfile(__DIR__ . '/../assets/app.js') ?>
    </script>
    </body>
    </html>


    <?php
} catch (Exception $e) {
    if (class_exists(Response::class)) {
        $response = new Response(StatusCode::HTTP_500);
        if ($e instanceof AbstractHttpException) {
            $response->setStatusCode($e->getStatusCode());
            $response->setBody($e->getBody());
        } else {
            error_log(sprintf('unknown error : %s  ---  %s', $e->getMessage(), var_export($e, true)));
        }
        $response->send();
    } else {
        $statusCode = StatusCode::tryFrom((int)$e->getCode());
        if ($e->getCode() > 0 && null !== $statusCode) {
            $response = new Response($statusCode);
            $response->send();
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            error_log(sprintf('unknown error : %s  ---  %s', $e->getMessage(), var_export($e, true)));
        }
    }
}
