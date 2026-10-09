param(
    [Parameter(Mandatory = $true)]
    [ValidatePattern('^v\d+\.\d+\.\d+([\-+][0-9A-Za-z.-]+)?$')]
    [string]$Tag,
    [string]$OutputDirectory
)

$ErrorActionPreference = 'Stop'
$git = Get-Command git -ErrorAction Stop
$repositoryRoot = Split-Path -Parent $PSScriptRoot
if ([string]::IsNullOrWhiteSpace($OutputDirectory)) {
    $OutputDirectory = Join-Path $repositoryRoot 'dist'
} elseif (-not [System.IO.Path]::IsPathRooted($OutputDirectory)) {
    $OutputDirectory = Join-Path $repositoryRoot $OutputDirectory
}

$status = & $git.Source -C $repositoryRoot status --porcelain
if ($LASTEXITCODE -ne 0) {
    throw 'Could not inspect Git status.'
}
if ($status) {
    throw 'Refusing to package a dirty worktree. Commit and tag the reviewed release first.'
}

& $git.Source -C $repositoryRoot rev-parse --verify "$Tag^{commit}" *> $null
if ($LASTEXITCODE -ne 0) {
    throw "Git tag '$Tag' does not resolve to a commit."
}

New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
$archivePath = Join-Path $OutputDirectory ("itims-$Tag.zip")
& $git.Source -C $repositoryRoot archive --format=zip --prefix='itims/' $Tag "--output=$archivePath"
if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $archivePath -PathType Leaf)) {
    throw 'Git could not create the release archive.'
}

Write-Output "Created $archivePath"
