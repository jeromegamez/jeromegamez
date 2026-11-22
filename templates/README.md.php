<?php

declare(strict_types=1);

/**
 * @var string $firebaseMonthlyDownloads
 * @var list<Repo> $recentContributions
 * @var list<Repo> $recentRepos
 * @var list<Release> $recentReleases
 * @var list<PullRequest> $recentPullRequests
 * @var list<Repo> $recentStars
 * @var list<User> $sponsors
 * @var list<User> $followers
 */
?>
### Hi there 👋

I'm Jérôme Gamez, a platform and backend engineer with 20+ years of experience.

I'm a Jack of all trades with a soft spot for PHP, legacy software and,
of course, Open Source software.

My mission is to make the work of all
developers (myself included) more productive and enjoyable.
I have a special affinity for code quality, stability, and legacy software,
and consider no code base to be unmaintainable 💪🏻.

My most prolific project is the (unofficial)
[Firebase Admin SDK for PHP](https://github.com/kreait/firebase-php) and its
related libraries, which are
[downloaded <?= $firebaseMonthlyDownloads ?> times a month](https://packagist.org/packages/kreait/firebase-php/stats), but I also publish and maintain many more Open Source
projects at [BESTE](https://github.com/beste) and my [personal](https://github.com/jeromegamez) accounts.

#### 👷 What I'm currently working on

<?php foreach ($recentContributions as $item): ?>
- [<?= $item->name ?>](<?= $item->url ?>)<?= $item->description !== '' ? ' - ' . $item->description : '' ?> (<?= $item->when ?>)
<?php endforeach; ?>

#### 🌱 My latest projects

<?php foreach ($recentRepos as $item): ?>
- [<?= $item->name ?>](<?= $item->url ?>)<?= $item->description !== '' ? ' - ' . $item->description : '' ?>

<?php endforeach; ?>

#### 🔭 Latest releases I've contributed to

<?php foreach ($recentReleases as $item): ?>
- [<?= $item->repoName ?>](<?= $item->repoUrl ?>) ([<?= $item->tagName ?>](<?= $item->releaseUrl ?>), <?= $item->publishedWhen ?>)<?= $item->description !== '' ? ' - ' . $item->description : '' ?>

<?php endforeach; ?>

#### 🔨 My recent Pull Requests

<?php foreach ($recentPullRequests as $item): ?>
- [<?= $item->title ?>](<?= $item->url ?>) on [<?= $item->repoName ?>](<?= $item->repoUrl ?>) (<?= $item->createdWhen ?>)
<?php endforeach; ?>

#### ⭐ Recent Stars

<?php foreach ($recentStars as $item): ?>
- [<?= $item->name ?>](<?= $item->url ?>)<?= $item->description !== '' ? ' - ' . $item->description : '' ?> (<?= $item->when ?>)
<?php endforeach; ?>

#### ❤️ These awesome people sponsor me (thank you!)

<?php foreach ($sponsors as $item): ?>
- [<?= $item->login ?>](<?= $item->url ?>) (<?= $item->when ?>)
<?php endforeach; ?>

#### 👯 Check out some of my recent followers

<?php foreach ($followers as $item): ?>
- [<?= $item->login ?>](<?= $item->url ?>)
<?php endforeach; ?>

#### 💬 Feedback

If you use one of my projects, I'd love to hear from you. Let me know what you
like and what needs being improved. And if you're a happy user of one or
many of my projects, please consider
[becoming a GitHub Sponsor](https://github.com/sponsors/jeromegamez)!

#### 📫 How to reach me

- Mastodon: https://phpc.social/@jay
- Email: github@jerome.gamez.name
