# OctoberCMS Styla Plugin Integration

## Integration

In order to fill a magazine page created within OctoberCMS with crawlable content a component must be attached to the magazine page. This solution is based on the [Plugin Documentation v2.0.1](https://docs.google.com/document/d/19FtUhlP0iiUZe_4NSGUMIDOLRmRPRHFbT-HyyRc8ejs/edit).

## Installation

The first step is installing the plugin by copying the `/styla` folder to the `/plugins` directory. That's it already. You should now see the `Magazine` plugin in the OctoberCMS Backend:

![Plugin](http://i.imgur.com/9Lh4agF.png)

## Setup

The setup of the folder integration can also completely be done from within the OctoberCMS Backend Interface.

* Create a new page for your root magazine, a single story and tag
* The URLs of the story and tag pages need to accept 1 optional URL parameter. Examples:

Root magazine: http://example.com/magazine
Story: http://example.com/magazine/story/:param?
Tag: http://example.com/magazine/tag/:param?

* Drag & drop the plugin from the components section to your pages code section.
* Place the following line where the magazine should appear on the site: `{% component 'Magazine' %}`
* Click on the component and set the _`domain`_ (domain of client) 
* Set _`type`_ according to the above URL: _Magazine_ (/magazine or other routes with the whole magazine), _Story_ (/story/*), or _Tag_ (/tag/*)
* Set _`integration`_ to the type of integration you want to use (snippet or CDN integration)
* _optional_: If you want to limit the feed to a certain amount of pages, write a number into the _`feedlimit`_ field. 
* Hit `[ENTER]` or just close the component settings to confirm.
* The above code will write code to the `head` and `body` placeholders in your layout file.
* In the layout file (e.g. [default.htm](https://github.com/styladev/shopmodules/blob/master/OctoberCMS/layouts/default.htm)) the placeholder should be located within the `<head>` and `<body>` sections: `{% placeholder head %}` or `{% placeholder body %}`
* Done.

With everything set up, a simple magazine page could look like this:

![Screenshot](http://www.styla.com/storage/app/media/Tutorial%20images/Screenshot%202015-08-10%2012.27.36.png)

_Reminder:_ In order for the plugin to work, the domain settings of a client need to look something like this:

```
{"embed":{"embedUser":"berliner-fahrradschau","magazineUrl":"berlinbicycleweek.com"},"routes":{"trends":"tag/%1$s","userTag":"tag/%2$s","story":"story/%2$s_%3$s"},"shop":false,"popup":{"width":360,"height":220}}
```

For a guide on how to set up OctoberCMS itself and it's hosting, please refer to our ["OctoberCMS & Fortrabbit"-Documentation"](https://docs.google.com/a/amazine.com/document/d/1ccFkUOSMFC8_0fU6i6gbF8G_1crN6F1UFe2cqy7ZV_M/edit?usp=sharing)

