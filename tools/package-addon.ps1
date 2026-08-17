param(
    [Parameter(Mandatory = $true)]
    [string]$AddonPath,

    [Parameter(Mandatory = $false)]
    [string]$DestinationPath
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Resolve-ExistingPath {
    param([string]$Path)
    $item = Get-Item -LiteralPath $Path -ErrorAction Stop
    return $item.FullName
}

$addonFullPath = Resolve-ExistingPath -Path $AddonPath
if (-not (Test-Path -LiteralPath $addonFullPath -PathType Container)) {
    throw "AddonPath must be a directory: $AddonPath"
}

$slug = Split-Path -Path $addonFullPath -Leaf
if ($slug -cnotmatch '^[a-z0-9_-]+$') {
    throw "Add-on folder name must be a sanitized lowercase slug: $slug"
}

$manifestPath = Join-Path $addonFullPath 'bbcs-addon.json'
if (-not (Test-Path -LiteralPath $manifestPath -PathType Leaf)) {
    throw "Missing bbcs-addon.json: $manifestPath"
}

$manifest = Get-Content -LiteralPath $manifestPath -Raw | ConvertFrom-Json
if ($manifest.slug -ne $slug) {
    throw "Manifest slug '$($manifest.slug)' must match folder name '$slug'."
}

$kitRoot = Split-Path -Parent $PSScriptRoot
$validator = Join-Path $PSScriptRoot 'validate-addon.php'
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if ($phpCmd -and (Test-Path -LiteralPath $validator -PathType Leaf)) {
    & $phpCmd.Source $validator $addonFullPath
    if ($LASTEXITCODE -ne 0) {
        throw "Source validation failed."
    }
} else {
    Write-Warning "PHP not found on PATH; source validation skipped."
}

if ([string]::IsNullOrWhiteSpace($DestinationPath)) {
    $distDir = Join-Path $kitRoot 'dist'
    $DestinationPath = Join-Path $distDir ($slug + '.zip')
}

$destinationFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($DestinationPath)
$destinationDir = Split-Path -Parent $destinationFullPath
if (-not (Test-Path -LiteralPath $destinationDir -PathType Container)) {
    New-Item -ItemType Directory -Path $destinationDir | Out-Null
}

# Build the archive with forward-slash entry names (System.IO.Compression).
# Compress-Archive on Windows writes backslash separators; WP unzip_file does
# not normalize them, so such packages break on Linux hosting.
$destStream = [System.IO.File]::Open($destinationFullPath, [System.IO.FileMode]::Create)
$zip = New-Object System.IO.Compression.ZipArchive($destStream, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    $files = Get-ChildItem -LiteralPath $addonFullPath -Recurse -File
    foreach ($file in $files) {
        $relative = $file.FullName.Substring($addonFullPath.Length).TrimStart('\', '/')
        $entryName = ($slug + '/' + $relative).Replace('\', '/')
        $entry = $zip.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $fileStream = [System.IO.File]::OpenRead($file.FullName)
        try {
            $fileStream.CopyTo($entryStream)
        } finally {
            $fileStream.Dispose()
            $entryStream.Dispose()
        }
    }
} finally {
    $zip.Dispose()
    $destStream.Dispose()
}

if ($phpCmd -and (Test-Path -LiteralPath $validator -PathType Leaf)) {
    & $phpCmd.Source $validator $destinationFullPath
    if ($LASTEXITCODE -ne 0) {
        throw "ZIP validation failed."
    }
} else {
    Write-Warning "PHP not found on PATH; ZIP validation skipped."
}

Write-Output "Created $destinationFullPath"
