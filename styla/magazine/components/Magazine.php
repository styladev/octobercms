<?php namespace Styla\Magazine\Components;

use Cms\Classes\ComponentBase;

class Magazine extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Magazine',
            'description' => 'Embeds a magazine, fetches crawable content and puts it on the site.'
        ];
    }

    public function defineProperties()
    {
        return [
            'domain' => [
                'description'       => 'Domain of the magazine.',
                'title'             => 'Domain',
                'default'           => '',
                'type'              => 'string'
            ],
            'type' => [
                'description'       => 'Type of content to be fetched.',
                'title'             => 'Type',
                'default'           => 'Story',
                'type'              => 'dropdown',
                'options'           => ['magazine' => 'Magazine', 'story' => 'Story', 'tag' => 'Tag']
            ],
            'integration' => [
                'description'       => 'Snippet or CDN Integration switch.',
                'title'             => 'Integration',
                'default'           => 'Snippet',
                'type'              => 'dropdown',
                'options'           => ['snippet' => 'Snippet', 'cdn' => 'CDN']
            ],
            'feedlimit' => [
                'description'       => 'Numbers of pages to be displayed.',
                'title'             => 'Feedlimit (optional)',
                'default'           => '',
                'type'              => 'string'
            ]
        ];
    }

    public function onRun()
    {
        $domain = $this->property('domain');            # styla Username
        $type = $this->property('type');                # type of the site (set in plugin)
        $param = $this->param('param');                 # url parameter
        $integration = $this->property('integration');  # Integration type
        $feedlimit = $this->property('feedlimit');      # Enable/Disable feedlimit

        // Pass to page - DEBUG reasons
        $this->page['domain'] = $domain;
        $this->page['type'] = $type;
        $this->page['param'] = $param;
        $this->page['integration'] = $integration;
        $this->page['feedlimit'] = $feedlimit;

        // --- Create Preloader ---------------------
        if($feedlimit){
            $this->page['Preloader'] = '<script id="stylaMagazine" src="//live.styla.com/scripts/preloader/'.$domain.'.js" data-feedlimit="'.$feedlimit.'"></script>';
        }
        else{
            $this->page['Preloader'] = '<script id="stylaMagazine" src="//live.styla.com/scripts/preloader/'.$domain.'.js"></script>';
        }

        // --- Fetch SEO content ----------------------
        switch($type){
            case 'tag':
                if($param != ""){
                    $json = @file_get_contents('http://seo.styla.com/clients/'.$domain.'?url=tag%2F'.$param);
                    if($json != FALSE){
                        $obj = json_decode($json);
                        $this->page['SEO_head'] = $obj->html->head;
                        $this->page['SEO_body'] = $obj->html->body;
                    }
                }
                break;

            case 'story':
                $json = @file_get_contents('http://seo.styla.com/clients/'.$domain.'?url=story%2F'.$param);
                if($json != FALSE){
                    $obj = json_decode($json);
                    $this->page['SEO_head'] = $obj->html->head;
                    $this->page['SEO_body'] = $obj->html->body;
                }
                break;

            case 'magazine': 
                $json = @file_get_contents('http://seo.styla.com/clients/'.$domain.'?url=user%2F'.$domain);
                if($json != FALSE){
                    $obj = json_decode($json);
                    $this->page['SEO_head'] = $obj->html->head;
                    $this->page['SEO_body'] = $obj->html->body;
                }
                break;
        }

        // --- CDN Stuff ----------------------------
        $filename = "version.txt";
        $currentTime = time();
        $age = null;
        $max_age = 100;
        $version = null;

        if (file_exists($filename)) {
            $age = $currentTime - filemtime($filename);
            if($age > $max_age){ // fetch new version from styla
                $currentVersion = @file_get_contents('http://live.styla.com/api/version/'.$domain);
                if($currentVersion != FALSE){
                    // Rewrite file to cache
                    $file = fopen($filename, "w");
                    $version = $currentVersion;
                    fwrite($file, $version);
                    fclose($file);
                }
                else{   // version couldnt be fetched from server -> read from cache
                    $file = fopen($filename, "r");
                    $version = fread($file, filesize($filename));
                    fclose($file);
                }
            }   
            else{   // Read version from cache
                $file = fopen($filename, "r");
                $version = fread($file, filesize($filename));
                fclose($file);
                //var_dump($version);
            }
        }
        else{   // file not present yet -> create it
            $currentVersion = @file_get_contents('http://live.styla.com/api/version/'.$domain);
            if($currentVersion != FALSE){
                // Rewrite file to cache
                $file = fopen($filename, "w");
                $version = $currentVersion;
                fwrite($file, $version);
                fclose($file);
            }
        }

        // Create code snippets
        $this->page['Styles'] = '<link rel="stylesheet" type="text/css" href="http://cdn.styla.com/styles/clients/'.$domain.'.css?v='.$version.'">';
        if($feedlimit){
            $this->page['Script'] = '<script async src="http://cdn.styla.com/scripts/clients/'.$domain.'.js?v='.$version.'" data-feedlimit="'.$feedlimit.'"></script>';
        }
        else{
            $this->page['Script'] = '<script async src="http://cdn.styla.com/scripts/clients/'.$domain.'.js?v='.$version.'"></script>';
        }

    }
}