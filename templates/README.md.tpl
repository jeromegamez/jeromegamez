### Hi there 👋

I'm Jérôme Gamez, a platform and backend engineer with 20+ years of experience.
I build [elvah](https://www.elvah.de) during the day and Open Source Software
at night.

I'm a Jack of all trades with a soft spot for PHP, legacy software and,
of course, Open Source software.

My hobby is my job and vice versa. My mission is to make the work of all
developers (myself included) more productive and enjoyable.
I have a special affinity for code quality, stability, and legacy software,
and consider no code base to be unmaintainable 💪🏻.

My most prolific project is the (unofficial)
[Firebase Admin SDK for PHP](https://github.com/kreait/firebase-php) and its
related libraries, which are
[downloaded 500K+ times a month](https://packagist.org/packages/kreait/firebase-php/stats), but I also publish and maintain many more Open Source
projects in the [BESTE](https://github.com/beste),
[kreait](https://github.com/kreait), and my
[personal](https://github.com/jeromegamez) GitHub organizations.

#### 👷 What I'm currently working on
{{range recentContributions 10}}
- [{{.Repo.Name}}]({{.Repo.URL}}) - {{.Repo.Description}} ({{humanize .OccurredAt}})
{{- end}}

#### 🌱 My latest projects
{{range recentRepos 10}}
- [{{.Name}}]({{.URL}}) - {{.Description}}
{{- end}}

#### 🔭 Latest releases I've contributed to
{{range recentReleases 10}}
- [{{.Name}}]({{.URL}}) ([{{.LastRelease.TagName}}]({{.LastRelease.URL}}), {{humanize .LastRelease.PublishedAt}}) - {{.Description}}
{{- end}}

#### 🔨 My recent Pull Requests
{{range recentPullRequests 10}}
- [{{.Title}}]({{.URL}}) on [{{.Repo.Name}}]({{.Repo.URL}}) ({{humanize .CreatedAt}})
{{- end}}

#### ⭐ Recent Stars
{{range recentStars 10}}
- [{{.Repo.Name}}]({{.Repo.URL}}) - {{.Repo.Description}} ({{humanize .StarredAt}})
{{- end}}

#### ❤️ These awesome people sponsor me (thank you!)
{{range sponsors 5}}
- [{{.User.Login}}]({{.User.URL}}) ({{humanize .CreatedAt}})
{{- end}}

#### 👯 Check out some of my recent followers
{{range followers 5}}
- [{{.Login}}]({{.URL}})
{{- end}}

#### 💬 Feedback

If you use one of my projects, I'd love to hear from you. Let me know what you
like and what needs being improved. And if you're a happy user of one or
many of my projects, please consider
[becoming a GitHub Sponsor](https://github.com/sponsors/jeromegamez)!

#### 📫 How to reach me

- Mastodon: https://phpc.social/@jay
- Email: github@jerome.gamez.name
