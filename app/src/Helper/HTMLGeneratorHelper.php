<?php

declare(strict_types = 1);

namespace Fayela\Helper;

class HTMLGeneratorHelper
{
    public function getIconSVG(
        string $name,
        string $classes = '',
        string $viewBox = '0 0 265 323',
        string $width = '1.5em',
        string $height = '1em'
    ): string {
        return sprintf(
            '<svg class="%s" width="%s" height="%s" version="1.1" viewBox="%s"><use xlink:href="#%s"></use></svg>',
            $classes,
            $width,
            $height,
            $viewBox,
            $name
        );
    }

    public function generateSortLink(
        string $fieldName,
        string $fieldLabel,
        ?string $currentSortBy,
        ?string $currentSortWay,
        bool $alignCenter = false
    ): string {
        $sortUpIcon = $this->getIconSVG(
            'up-arrow',
            'fayela--filelist--row--header--icon-sort--top',
            '0 0 12 6',
            '1em',
            '.5em'
        );
        $sortDownIcon = $this->getIconSVG(
            'down-arrow',
            'fayela--filelist--row--header--icon-sort--bottom',
            '0 0 12 6',
            '1em',
            '.5em'
        );

        if (null !== $currentSortBy && $currentSortBy !== $fieldName) {
            $currentSortWay = null;
        }

        $icons = match ($currentSortWay) {
            'asc' => $sortUpIcon,
            'desc' => $sortDownIcon,
            default => $sortUpIcon . $sortDownIcon,
        };

        $link = match ($currentSortWay) {
            'asc' => [
                'sortBy' => $fieldName,
                'sortWay' => 'desc',
            ],
            default => [
                'sortBy' => $fieldName,
                'sortWay' => 'asc',
            ],
        };

        return sprintf(
            '<a href="%s" class="%s"><span class="fayela--filelist--row--header--icon-sort" >%s</span><span>%s</span></a>',
            '?' . http_build_query($link),
            $alignCenter ? 'text-center' : '',
            $icons,
            $fieldLabel
        );
    }


    public function generateFilelistParentDirectoryRow(): string
    {
        return sprintf(
            '
                <a class="fayela--filelist--row" href="..">
                    <span class="fayela--filelist--row--link">
                        %s
                        <span class="name">..</span>
                    </span>
                </a>',
            $this->getIconSVG('folder-shortcut')
        );
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $customization
     */
    public function generateFilelistRow(array $item, array $customization = []): string
    {
        $folderDescription = '';
        if (isset($customization['description'])) {
            $folderDescription = sprintf('<span>%s</span>', $customization['description']);
        }

        $rowLink = sprintf(
            '<span class="fayela--filelist--row--link">%s<span class="fayela--filelist--row--link--name"><span>%s</span>%s</span></span>',
            $this->getIconSVG(true === $item['isFile'] ? 'file' : 'folder'),
            $customization['name'] ?? $item['name'],
            $folderDescription
        );

        $totalFolders = '';
        if (isset($item['children_total_folders']) && $item['children_total_folders'] > 0) {
            $totalFolders = $this->getIconSVG('folder') . $item['children_total_folders'];
        }

        $totalFiles = '';
        if (isset($item['children_total_files']) && $item['children_total_files'] > 0) {
            $totalFiles = $this->getIconSVG('file') . $item['children_total_files'];
        }

        $rowFiles = sprintf(
            '<span class="fayela--filelist--row--files"><span>%s</span><span>%s</span></span>',
            $totalFolders,
            $totalFiles
        );

        $rowSize = sprintf('<span class="fayela--filelist--row--size">%s</span>', $item['size_human']);
        $rowCreatedAt = sprintf('<span class="fayela--filelist--row--created-at">%s</span>', date('d/m/Y', $item['timestamp_create']));

        return sprintf(
            '<a class="fayela--filelist--row" href="%s">%s</a>',
            $item['public_path'],
            $rowLink . $rowFiles . $rowSize . $rowCreatedAt
        );
    }

    /**
     * @param array<int, array{anchor: string, href: ?string}> $breadcrumb
     */
    public function getBreadcrumb(
        array $breadcrumb,
        string $linkTemplate = '<a href="%s">%s</a>',
        string $separator = ' > '
    ): string {
        return implode(
            $separator,
            array_map(
                static fn ($row) => match ($row['href']) {
                    null => $row['anchor'],
                    default => sprintf(
                        $linkTemplate,
                        $row['href'],
                        $row['anchor']
                    )
                },
                $breadcrumb
            )
        );
    }

    /**
     * @param array<string, mixed> $directories
     */
    public function generateRecentFilesColumns(array $directories): string
    {
        return sprintf(
            '<div class="fayela--filecolumns">%s</div>',
            implode(
                '',
                array_map(fn ($item) => sprintf(
                    '<div class="fayela--filecolumns--column"><h2>Last added in %s</h2> %s </div>',
                    $item['name'],
                    implode(
                        '',
                        array_map(
                            fn ($lastCreatedFile) => $this->generateFilelistRow($lastCreatedFile),
                            $item['last_created_files']
                        )
                    )
                ), $directories)
            )
        );
    }
}
