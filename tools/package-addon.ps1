param(
    [Parameter(Mandatory = $true)]
    [string]$AddonPath,

    [Parameter(Mandatory = $false)]
    [string]$DestinationPath
)

$ErrorActionPreference = 'Stop'

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
if ((Get-Command php -ErrorAction SilentlyContinue) -and (Test-Path -LiteralPath $validator -PathType Leaf)) {
    & php $validator $addonFullPath
    if ($LASTEXITCODE -ne 0) {
        throw "Source validation failed."
    }
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

$parent = Split-Path -Parent $addonFullPath
$sourceForArchive = Join-Path $parent $slug

if (Test-Path -LiteralPath $destinationFullPath -PathType Leaf) {
    Remove-Item -LiteralPath $destinationFullPath -Force
}

Compress-Archive -Path $sourceForArchive -DestinationPath $destinationFullPath -Force

if ((Get-Command php -ErrorAction SilentlyContinue) -and (Test-Path -LiteralPath $validator -PathType Leaf)) {
    & php $validator $destinationFullPath
    if ($LASTEXITCODE -ne 0) {
        throw "ZIP validation failed."
    }
}

Write-Output "Created $destinationFullPath"

