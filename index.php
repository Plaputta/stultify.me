<?php
/**
 * Proof Of Concept for stultify.me
 */

// First, load foreign page:

if (!isset($_GET['target'])) {
    die('Missing target!');
}
if (!isset($_GET['top'])) {
    die('Missing top!');
}
if (!isset($_GET['left'])) {
    die('Missing left!');
}
if (!isset($_GET['zoom'])) {
    die('Missing zoom!');
}
if (!isset($_GET['id'])) {
    die('Missing id!');
}

$target = urldecode($_GET['target']);
$top = urldecode($_GET['top']);
$left = urldecode($_GET['left']);
$zoom = urldecode($_GET['zoom']);
$id = urldecode($_GET['id']);


// Split target:
if (preg_match('/\b(?P<protocol>https?):\/\/(?P<domain>[-A-Z0-9.]+)(?P<file>\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?(?P<parameters>\?[A-Z0-9+&@#\/%=~_|!:,.;]*)?/i', $target, $regs)) {
    if (isset($regs['file'])) {
        $pathParts = explode('/', $regs['file']);
        if (count($pathParts) && strpos($pathParts[count($pathParts) - 1], '.') !== false) {
            // Remove last part if file
            unset($pathParts[count($pathParts) - 1]);
        }
        $baseHref = $regs['protocol'] . '://' . $regs['domain'] . '/' . (count($pathParts) ? implode('/', $pathParts) : '') . '/';
    } else {
        $baseHref = $regs['protocol'] . '://' . $regs['domain'] . '/';
    }

} else {
    die('Malformed target!');
}

$ch = curl_init($target);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$page = curl_exec($ch);
curl_close($ch);

if (!$page) {
    die('Unable to load target!');
}


// If we already have a base href definition, skip this.
/* RegexBuddy code: ﻿<\s*base\s+.*href\s*=\s*(["']?)(?P<url>[-A-Z0-9+&@#/%?=~_|$!:,.;]+)\1.*?> (Case insensitive) */
if (!preg_match('/<\s*base\s+.*href\s*=\s*(["\']?)(?P<url>[-A-Z0-9+&@#\/%?=~_|$!:,.;]+)\1.*?>/i', $page)) {
    $page = preg_replace('%(<\s*head\s*.*?>)%i', '$1<base href="'.$baseHref.'" />', $page, 1, $count);
    if (!$count) {
        // fallback, just add to top
        $page = '<base href="'.$baseHref.'" />'.$page;
    }
    unset($count);
}

// Inject HTML
// ToDo: Now this is a mock, the UI would highlight all relative elements so the user can select one as base target

// 625x255
$injection = '<img src="http://stultify.me/moustache.png" alt="" width="'.round(625*$zoom).'" height="'.round(255*$zoom).'" style="z-index:999999999;top:'.$top.'px;left:'.$left.'px;position:absolute;" />';
$page = preg_replace('/(<\s*[a-z0-9]+\s+([^>]*\s+)?id\s*=\s*(["\'])'.$id.'\3.*?>)/i','$1'.$injection,$page,1,$count);

if (!$count) {
    die('Injection failed.');
}

echo $page;

