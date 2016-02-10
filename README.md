# OctoberCMS Styla Plugin Integration

## Integration

In order to fill a magazine page created within OctoberCMS with crawlable content a component must be attached to the magazine page. This solution is based on the [Plugin Documentation v2.0.1](https://docs.google.com/document/d/19FtUhlP0iiUZe_4NSGUMIDOLRmRPRHFbT-HyyRc8ejs/edit).

## Installation

The first step is installing the plugin by copying the `/styla` folder to the `/plugins` directory. That's it already. You should now see the `Magazine` plugin in the OctoberCMS Backend:

![Plugin](http://i.imgur.com/9Lh4agF.png)

## Setup

The setup of the folder integration can also completely be done from within the OctoberCMS Backend Interface.

* Create a new page for your magazine: The URL of the page should reflect the `rootPath` of the desired magazine, e.g. `/<rootPath>`
* Create another Page for your magazine which will serve as a wildcard: The URL should be something like this `/<rootPath>/:param*`

---

* Drag & drop the plugin from the components section to both pages code section.
* Place the following line where the magazine should appear on the site: `{% component 'Magazine' %}`

---

* In order to configure the plugin click on the component. You'll see the following settings:
1. `Alias` – __required__, default: `Magazine` – the internal name which OctoberCMS will use to load the component
2. `Domain` – __required__ – the domain of the desired magazine
3. `CDN Server` – __required__, default: `http://cdn.styla.com/` – the server that provides the necessary scripts and styles for the magazine
4. `SEO Server` – __required__, default: `http://seo.styla.com/clients/` – the server that provides SEO information for the magazine
5. `Version Server` – __required__, default: `http://live.styla.com/api/version/` – the server that provides the latest version number of the scripts and styles for the magazine
6. `Feedlimit` – _optional_, the amount of stories to be shown in the magazine, adds the `data-feedlimit` attribute
7. `Tag` – _optional_, the magazine will only show stories with the specified tag, adds the `data-tag` attribute
8. `Debug`– _optional_, when enabled Debug information will be displayed above the magazine

---

* When a page with the plugin is called the component will write fetched SEO information to the `head` in your layout file
* Therefor the `<head>` section of your layout should look something like this (in regards to the placeholder):

```
<head>
    <title>October CMS - {{ this.page.title }}</title>
    <meta name="author" content="October CMS">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ 'assets/images/october.png'|theme }}" />
    {% placeholder head %}
    {% styles %}
    <link href="{{ [
        'assets/css/theme.css'
    ]|theme }}" rel="stylesheet">
</head>
```

* Done.

With everything set up, a simple magazine page could look like this:

![Screenshot](http://i.imgur.com/SkSnQGt.png)
