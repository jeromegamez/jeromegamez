#!/usr/bin/env php
<?php

declare(strict_types=1);

const GRAPHQL_ENDPOINT = 'https://api.github.com/graphql';

const GRAPHQL_QUERY = <<<'GRAPHQL'
query {
  viewer {
    login
    contributionsCollection {
      commitContributionsByRepository(maxRepositories: 50) {
        repository {
          nameWithOwner
          url
          description
          isPrivate
          isArchived
          isFork
        }
        contributions(first: 1, orderBy: {field: OCCURRED_AT, direction: DESC}) {
          nodes {
            occurredAt
          }
        }
      }
    }
    repositories(
      first: 30
      privacy: PUBLIC
      isFork: false
      ownerAffiliations: OWNER
      orderBy: {field: CREATED_AT, direction: DESC}
    ) {
      nodes {
        nameWithOwner
        url
        description
        isArchived
      }
    }
    repositoriesContributedTo(
      first: 50
      privacy: PUBLIC
      contributionTypes: [COMMIT, PULL_REQUEST]
      orderBy: {field: PUSHED_AT, direction: DESC}
    ) {
      nodes {
        nameWithOwner
        url
        description
        isArchived
        isFork
        latestRelease {
          tagName
          url
          publishedAt
          description
          author {
            login
          }
        }
      }
    }
    pullRequests(first: 20, orderBy: {field: CREATED_AT, direction: DESC}) {
      nodes {
        title
        url
        createdAt
        repository {
          nameWithOwner
          url
          isPrivate
          isArchived
          isFork
        }
      }
    }
    starredRepositories(first: 20, orderBy: {field: STARRED_AT, direction: DESC}) {
      edges {
        starredAt
        node {
          nameWithOwner
          url
          description
          isPrivate
          isArchived
          isFork
        }
      }
    }
    sponsorshipsAsMaintainer(
      first: 5
      includePrivate: false
      orderBy: {field: CREATED_AT, direction: DESC}
    ) {
      nodes {
        createdAt
        sponsorEntity {
          ... on User {
            login
            url
          }
          ... on Organization {
            login
            url
          }
        }
      }
    }
    followers(last: 5) {
      nodes {
        login
        url
      }
    }
  }
}
GRAPHQL;

final readonly class Repo
{
    public function __construct(
        public string $name,
        public string $url,
        public string $description,
        public string $when = '',
        public string $sortKey = '',
    ) {}
}

final readonly class Release
{
    public function __construct(
        public string $repoName,
        public string $repoUrl,
        public string $description,
        public string $tagName,
        public string $releaseUrl,
        public string $publishedWhen,
        public string $publishedAt,
    ) {}
}

final readonly class PullRequest
{
    public function __construct(
        public string $title,
        public string $url,
        public string $repoName,
        public string $repoUrl,
        public string $createdWhen,
    ) {}
}

final readonly class User
{
    public function __construct(
        public string $login,
        public string $url,
        public string $when = '',
    ) {}
}

function resolveToken(): string
{
    $token = getenv('GITHUB_TOKEN');
    if (is_string($token) && trim($token) !== '') {
        return trim($token);
    }

    $output = [];
    $exitCode = 0;
    @exec('gh auth token 2>/dev/null', $output, $exitCode);
    if ($exitCode === 0 && isset($output[0]) && trim($output[0]) !== '') {
        return trim($output[0]);
    }

    fwrite(STDERR, "Error: GITHUB_TOKEN is not set and `gh auth token` failed.\n");
    exit(1);
}

/**
 * @return array<string, mixed>
 */
function fetchViewerData(string $token): array
{
    $ch = curl_init(GRAPHQL_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Authorization: bearer ' . $token,
            'Content-Type: application/json',
            'User-Agent: jeromegamez-readme-updater',
        ],
        CURLOPT_POSTFIELDS => json_encode(['query' => GRAPHQL_QUERY], JSON_THROW_ON_ERROR),
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if (!is_string($body) || $error !== '') {
        fwrite(STDERR, "Error: GitHub GraphQL request failed: {$error}\n");
        exit(1);
    }

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

    if ($status >= 400) {
        $details = json_encode($decoded['errors'] ?? $decoded, JSON_PRETTY_PRINT);
        fwrite(STDERR, "Error: GitHub GraphQL returned HTTP {$status}: {$details}\n");
        exit(1);
    }

    // Ignore SAML SSO enforcement errors so unauthorized org repos are simply skipped.
    $fatalErrors = array_filter(
        $decoded['errors'] ?? [],
        static fn (array $err): bool => ($err['type'] ?? '') !== 'FORBIDDEN'
            || !str_contains((string) ($err['message'] ?? ''), 'SAML'),
    );
    if ($fatalErrors !== [] || !isset($decoded['data']['viewer'])) {
        $details = json_encode($fatalErrors ?: $decoded, JSON_PRETTY_PRINT);
        fwrite(STDERR, "Error: GitHub GraphQL failed: {$details}\n");
        exit(1);
    }

    /** @var array<string, mixed> */
    return $decoded['data']['viewer'];
}

function fetchPackagistMonthlyDownloads(string $package): string
{
    $ch = curl_init("https://packagist.org/packages/{$package}.json");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['User-Agent: jeromegamez-readme-updater'],
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if (!is_string($body) || $error !== '' || $status >= 400) {
        fwrite(STDERR, "Error: Packagist request failed (HTTP {$status}): {$error}\n");
        exit(1);
    }

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    $monthly = (int) ($decoded['package']['downloads']['monthly'] ?? 0);

    if ($monthly >= 1_000_000) {
        $formatted = rtrim(rtrim(number_format(floor($monthly / 100_000) / 10, 1), '0'), '.');

        return "{$formatted}M+";
    }

    return number_format(floor($monthly / 1_000)) . 'K+';
}

function cleanText(?string $text): string
{
    if ($text === null) {
        return '';
    }

    return trim((string) preg_replace('/\s+/', ' ', $text));
}

function humanize(string $isoDate, DateTimeImmutable $now): string
{
    $date = new DateTimeImmutable($isoDate);
    $days = max(0, (int) floor(($now->getTimestamp() - $date->getTimestamp()) / 86400));

    return match (true) {
        $days === 0 => 'today',
        $days === 1 => '1 day ago',
        $days < 7 => "{$days} days ago",
        $days < 14 => '1 week ago',
        $days < 30 => intdiv($days, 7) . ' weeks ago',
        $days < 60 => '1 month ago',
        $days < 365 => intdiv($days, 30) . ' months ago',
        $days < 730 => '1 year ago',
        default => intdiv($days, 365) . ' years ago',
    };
}

$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$viewer = fetchViewerData(resolveToken());
$viewerLogin = (string) $viewer['login'];
$firebaseMonthlyDownloads = fetchPackagistMonthlyDownloads('kreait/firebase-php');

$recentContributions = [];
foreach ($viewer['contributionsCollection']['commitContributionsByRepository'] as $entry) {
    $repo = $entry['repository'] ?? null;
    $occurredAt = $entry['contributions']['nodes'][0]['occurredAt'] ?? null;
    if (!is_array($repo) || $repo['isPrivate'] || $repo['isArchived'] || $repo['isFork'] || $occurredAt === null) {
        continue;
    }
    $recentContributions[] = new Repo(
        name: $repo['nameWithOwner'],
        url: $repo['url'],
        description: cleanText($repo['description']),
        when: humanize($occurredAt, $now),
        sortKey: $occurredAt,
    );
}
usort($recentContributions, static fn (Repo $a, Repo $b): int => $b->sortKey <=> $a->sortKey);
$recentContributions = array_slice($recentContributions, 0, 10);

$recentRepos = [];
foreach ($viewer['repositories']['nodes'] as $repo) {
    if (!is_array($repo) || $repo['isArchived']) {
        continue;
    }
    $recentRepos[] = new Repo(
        name: $repo['nameWithOwner'],
        url: $repo['url'],
        description: cleanText($repo['description']),
    );
    if (count($recentRepos) === 10) {
        break;
    }
}

$recentReleases = [];
foreach ($viewer['repositoriesContributedTo']['nodes'] as $repo) {
    if (!is_array($repo) || $repo['isArchived'] || $repo['isFork']) {
        continue;
    }
    $release = $repo['latestRelease'] ?? null;
    if ($release === null || empty($release['publishedAt'])) {
        continue;
    }
    $isAuthor = strcasecmp((string) ($release['author']['login'] ?? ''), $viewerLogin) === 0;
    $isMentioned = stripos((string) ($release['description'] ?? ''), '@' . $viewerLogin) !== false;
    if (!$isAuthor && !$isMentioned) {
        continue;
    }
    $recentReleases[] = new Release(
        repoName: $repo['nameWithOwner'],
        repoUrl: $repo['url'],
        description: cleanText($repo['description']),
        tagName: $release['tagName'],
        releaseUrl: $release['url'],
        publishedWhen: humanize($release['publishedAt'], $now),
        publishedAt: $release['publishedAt'],
    );
}
usort($recentReleases, static fn (Release $a, Release $b): int => $b->publishedAt <=> $a->publishedAt);
$recentReleases = array_slice($recentReleases, 0, 10);

$recentPullRequests = [];
foreach ($viewer['pullRequests']['nodes'] as $pr) {
    $repo = $pr['repository'] ?? null;
    if (!is_array($pr) || !is_array($repo) || $repo['isPrivate'] || $repo['isArchived'] || $repo['isFork']) {
        continue;
    }
    $recentPullRequests[] = new PullRequest(
        title: cleanText($pr['title']),
        url: $pr['url'],
        repoName: $pr['repository']['nameWithOwner'],
        repoUrl: $pr['repository']['url'],
        createdWhen: humanize($pr['createdAt'], $now),
    );
    if (count($recentPullRequests) === 10) {
        break;
    }
}

$recentStars = [];
foreach ($viewer['starredRepositories']['edges'] as $edge) {
    $repo = $edge['node'] ?? null;
    if (!is_array($repo) || $repo['isPrivate'] || $repo['isArchived'] || $repo['isFork']) {
        continue;
    }
    $recentStars[] = new Repo(
        name: $repo['nameWithOwner'],
        url: $repo['url'],
        description: cleanText($repo['description']),
        when: humanize($edge['starredAt'], $now),
    );
    if (count($recentStars) === 10) {
        break;
    }
}

$sponsors = [];
foreach ($viewer['sponsorshipsAsMaintainer']['nodes'] as $sponsorship) {
    $entity = $sponsorship['sponsorEntity'] ?? null;
    if (!is_array($entity) || empty($entity['login'])) {
        continue;
    }
    $sponsors[] = new User(
        login: $entity['login'],
        url: $entity['url'],
        when: humanize($sponsorship['createdAt'], $now),
    );
}

$followers = array_reverse(array_map(
    static fn (array $user): User => new User(
        login: $user['login'],
        url: $user['url'],
    ),
    $viewer['followers']['nodes'],
));

ob_start();
require __DIR__ . '/templates/README.md.php';
$rendered = (string) ob_get_clean();

file_put_contents(__DIR__ . '/README.md', $rendered);
