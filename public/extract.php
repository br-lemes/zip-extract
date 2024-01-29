<?php

declare(strict_types=1);

$base = __DIR__ . '/..';
$public = 'public';
$self = basename(__FILE__);

function render_document(string $child = ''): void
{ ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ZIP Extract</title>
        <style>
            body {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 80vh;
            }
        </style>
    </head>

    <body>
        <?= $child ?>
    </body>

    </html>
<?php
    exit;
}

function render_message(string $title, string $message): string
{
    ob_start();
?>
    <h1><?= $title ?></h1>
    <ul><?= $message ?></ul>
<?php
    return ob_get_clean();
}

function render_error(string $message): string
{
    return render_message('Error', "<li>$message</li>");
}

function render_success(array $message): string
{
    return render_message(
        'Success',
        join('', array_map(fn ($message) => "<li>$message</li>", $message))
    );
}

function del_tree(string $dir): bool
{
    global $base;
    global $public;
    global $self;
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    $isPublic = $dir === "$base/$public";
    foreach ($files as $file) {
        if ($isPublic && $file === $self) {
            continue;
        }
        (is_dir("$dir/$file")) ? del_tree("$dir/$file") : unlink("$dir/$file");
    }
    if (!$isPublic) {
        return rmdir($dir);
    }
    return true;
}

function zip_extract(string $file): bool
{
    global $base;
    $zip = new ZipArchive();
    if ($zip->open("$base/$file") === true) {
        $extract = $zip->extractTo($base);
        $zip->close();
        return $extract;
    }
    return false;
}

function clean(string $path): void
{
    global $base;
    if (del_tree("$base/$path")) {
        return;
    }
    render_document(render_error("Unable to delete $path"));
}

$success = [];

$system = file_exists("$base/system.zip");
$vendor = file_exists("$base/vendor.zip");

if (!$system && !$vendor) {
    render_document(render_error('No system.zip or vendor.zip found'));
}

if ($system) {
    clean('.env');
    clean('modules');
    clean($public);
    clean('src');
    if (!zip_extract('system.zip')) {
        render_document(render_error("Unable to extract system.zip"));
    };
    $success[] = 'Extracted system.zip';
    if (!unlink("$base/system.zip")) {
        $success[] = 'Unable to delete system.zip';
    };
}

if ($vendor) {
    clean('vendor');
    if (!zip_extract('vendor.zip')) {
        render_document(render_error("Unable to extract vendor.zip"));
    };
    $success[] = 'Extracted vendor.zip';
    if (!unlink("$base/vendor.zip")) {
        $success[] = 'Unable to delete vendor.zip';
    };
}

render_document(render_success($success));
