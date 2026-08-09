<?php
/**
 * Local music API
 * ?action=list                    → all songs
 * ?action=lyrics&file=xxx.lrc     → lyrics text
 * ?action=cover&file=xxx.mp3      → cover art (base64 data URI or default)
 */
$musicDir = __DIR__ . '/assets/music/';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $songs = [];
    if (is_dir($musicDir)) {
        $files = glob($musicDir . '*.mp3');
        foreach ($files as $f) {
            $name = pathinfo($f, PATHINFO_FILENAME);
            $lrcFile = $musicDir . $name . '.lrc';
            $songs[] = [
                'name'   => $name,
                'url'    => 'assets/music/' . basename($f),
                'hasLrc' => file_exists($lrcFile),
                'hasCover' => hasCover($f),
            ];
        }
    }
    echo json_encode($songs);

} elseif ($action === 'lyrics') {
    $file = basename($_GET['file'] ?? '');
    $path = $musicDir . $file;
    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'lrc') {
        header('Content-Type: text/plain; charset=utf-8');
        readfile($path);
    } else {
        http_response_code(404);
        echo 'Not found';
    }

} elseif ($action === 'cover') {
    $file = basename($_GET['file'] ?? '');
    $path = $musicDir . $file;
    if (!file_exists($path)) { http_response_code(404); echo json_encode(['error'=>'File not found']); exit; }

    $cover = extractCover($path);
    if ($cover) {
        echo json_encode(['cover' => $cover]);
    } else {
        echo json_encode(['cover' => null]);
    }

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}

// ========== ID3v2 Cover Art Extractor ==========

function hasCover(string $mp3Path): bool {
    return extractCover($mp3Path) !== null;
}

function extractCover(string $mp3Path): ?string {
    $fp = @fopen($mp3Path, 'rb');
    if (!$fp) return null;

    // Read ID3v2 header (10 bytes)
    $header = fread($fp, 10);
    if (strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
        fclose($fp);
        return null;
    }

    $version = ord($header[3]);
    // Tag size: 4 bytes synchsafe (bit 7 ignored in each byte → 28-bit number)
    $size = (ord($header[6]) << 21) | (ord($header[7]) << 14) | (ord($header[8]) << 7) | ord($header[9]);

    $pos = 10;
    // Skip extended header if present (v2.4 flag bit 6)
    $flags = ord($header[5]);
    if (($flags & 0x40) && $version >= 4) {
        $extHeaderSize = unpack('N', fread($fp, 4))[1];
        if ($version >= 4) $extHeaderSize = synchsafeToInt($extHeaderSize);
        fseek($fp, $extHeaderSize - 4, SEEK_CUR);
        $pos += $extHeaderSize;
    }

    // Scan frames
    $endPos = $pos + $size;
    while ($pos < $endPos - 10) {
        $frameHeader = fread($fp, 10);
        if (strlen($frameHeader) < 10) break;

        $frameId = substr($frameHeader, 0, 4);
        // Frame ID of all zeros = padding reached
        if ($frameId === "\x00\x00\x00\x00") break;

        $frameSize = unpack('N', substr($frameHeader, 4, 4))[1];
        if ($version >= 4) $frameSize = synchsafeToInt($frameSize);
        if ($frameSize <= 0 || $frameSize > $size) break;

        $pos += 10;

        if ($frameId === 'APIC') {
            // APIC: encoding(1) + mime(0-terminated) + picType(1) + desc(0-terminated) + imageData
            $apicData = fread($fp, $frameSize);
            if (strlen($apicData) < 4) break;

            $enc = ord($apicData[0]);
            $endMime = strpos($apicData, "\x00", 1);
            if ($endMime === false) break;
            $mime = substr($apicData, 1, $endMime - 1);

            $descStart = $endMime + 2; // skip null + picType(1)
            // For encoding 1/2 (UTF-16), description is 2-byte null terminated
            if ($enc === 1 || $enc === 2) {
                $endDesc = strpos($apicData, "\x00\x00", $descStart);
                if ($endDesc === false) $endDesc = $descStart;
                else $endDesc += 2;
            } else {
                $endDesc = strpos($apicData, "\x00", $descStart);
                if ($endDesc === false) $endDesc = $descStart;
                else $endDesc += 1;
            }

            $imageData = substr($apicData, $endDesc);
            fclose($fp);
            return 'data:' . $mime . ';base64,' . base64_encode($imageData);
        }

        fseek($fp, $frameSize, SEEK_CUR);
        $pos += $frameSize;
    }

    fclose($fp);
    return null;
}

function synchsafeToInt(int $val): int {
    return ($val & 0x7F000000) >> 3 | ($val & 0x7F0000) >> 2 | ($val & 0x7F00) >> 1 | ($val & 0x7F);
}
