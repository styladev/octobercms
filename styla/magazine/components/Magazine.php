<?php namespace Styla\Magazine\Components;

use Cms\Classes\ComponentBase;
use Request;
use Cache;

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
                'default'           => 'magazine',
                'type'              => 'dropdown',
                'options'           => ['magazine' => 'Magazine', 'story' => 'Story', 'tag' => 'Tag']
            ],
            'integration' => [
                'description'       => 'Snippet or CDN Integration switch.',
                'title'             => 'Integration',
                'default'           => 'snippet',
                'type'              => 'dropdown',
                'options'           => ['snippet' => 'Snippet', 'cdn' => 'CDN']
            ],
            'feedlimit' => [
                'description'       => 'Numbers of pages to be displayed.',
                'title'             => 'Feedlimit (optional)',
                'default'           => '',
                'type'              => 'string'
            ],
            'duration' => [
                'description'       => 'Duration in minutes.',
                'title'             => 'Caching duration',
                'default'           => 20,
                'type'              => 'string',
                'required'			=> true,
                'validationPattern' => '^[0-9]+$',
				'validationMessage' => 'The Duration property can contain only numeric symbols'
            ],
            'environment' => [
	            'description'       => 'Source of the magazine domain. Stage only works with Snippet integration',
                'title'             => 'Environment',
                'default'           => 'live',
                'type'              => 'dropdown',
                'depends'     		=> ['integration'],
                'options'           => ['live' => 'Live', 'stage' => 'Stage']
            ],
        ];
    }
    
    public function getEnvironmentOptions()
	{
	    $integration = Request::input('integration'); // Load the country property value from POST
	
	    $environments = [
	        'snippet' => ['live'=>'Live', 'stage'=>'Stage'],
	        'cdn' => ['live'=>'Live']
	    ];
	
	    return $environments[$integration];
	}

    public function onRun()
    {
        // Pass to page - DEBUG reasons
        $this->page['domain'] = $this->property('domain');            # styla Username
        $this->page['type'] = $this->property('type');                # type of the site (set in plugin)
        $this->page['param'] = $this->param('param');                 # url parameter
        $this->page['integration'] = $this->property('integration');  # Integration type
        $this->page['feedlimit'] = $this->property('feedlimit');      # Enable/Disable feedlimit
        $this->page['duration'] = $this->property('duration');        # Duration for caching
        $this->page['environment'] = $this->property('environment');  # source for magazine (live.styla.com or stage.styla.com)
        
        // --- Create Snippet ---------------------
        
        if($this->property('feedlimit')){
            $this->page['Preloader'] = '<script id="stylaMagazine" src="//'.$this->property('environment').'.styla.com/scripts/preloader/'.$this->property('domain').'.js" data-feedlimit="'.$this->property('feedlimit').'"></script>';
        }
        else{
            $this->page['Preloader'] = '<script id="stylaMagazine" src="//'.$this->property('environment').'.styla.com/scripts/preloader/'.$this->property('domain').'.js"></script>';
        }

        // --- Fetch SEO content ----------------------
        
        switch($this->property('type')){
            case 'tag':
                if($this->param('param') != ""){
                    $json = $this->fetchAndRemember( $this->property('duration'), $this->property('environment'), 'tag' );
                    if($json != FALSE){
                        $obj = json_decode($json);
                        $this->page['SEO_head'] = $obj->html->head;
                        $this->page['SEO_body'] = $obj->html->body;
                    }
                }
                break;

            case 'story':
                $json = $this->fetchAndRemember( $this->property('duration'), $this->property('environment'), 'story' );
                if($json != FALSE){
                    $obj = json_decode($json);
                    $this->page['SEO_head'] = $obj->html->head;
                    $this->page['SEO_body'] = $obj->html->body;
                }
                break;

            case 'magazine':
            	$json = $this->fetchAndRemember( $this->property('duration'), $this->property('environment'), 'magazine' );
            	if($json != FALSE){
                    $obj = json_decode($json);
                    $this->page['SEO_head'] = $obj->html->head;
                    $this->page['SEO_body'] = $obj->html->body;
                }
                break;
        }

        // --- CDN Version ----------------------------

        $version = $this->fetchAndRemember( $this->property('duration'), $this->property('environment'), 'version' );
        $this->page['version'] = $version;

        // --- Create CDN code snippets ---------------------------
        
        $this->page['Styles'] = '<link rel="stylesheet" type="text/css" href="http://cdn.styla.com/styles/clients/'.$this->property('domain').'.css?v='.$version.'">';
        
        if($this->property('feedlimit')){
            $this->page['Script'] = '<script async src="http://cdn.styla.com/scripts/clients/'.$this->property('domain').'.js?v='.$version.'" data-feedlimit="'.$this->property('feedlimit').'"></script>';
        }
        else{
            $this->page['Script'] = '<script async src="http://cdn.styla.com/scripts/clients/'.$this->property('domain').'.js?v='.$version.'"></script>';
        }
    }
    
    /***************************************
	 * C A C H E  F U N C T I O N S
	 ***************************************/
	 
	// Fetch and remember
	public function fetchAndRemember($minutes, $environment, $type){
		
		$key = '';
		$url = '';
		
		switch($type){
			
			case 'version':
				$key = 'styla_CDN_version';
				
				if($environment == 'live'){
					$url = 'http://live.styla.com/api/version/'.$this->property('domain');
				}
				else{
					$url = 'http://stage.styla.com/api/version/'.$this->property('domain');
				}
				break;
			
			case 'magazine':
				$key = 'styla_SEO_magazine';
				
				if($environment == 'live'){
					$url = 'http://seo.styla.com/clients/'.$this->property('domain').'?url=user%2F'.$this->property('domain');
				}
				else{
					$url = 'http://seoapistage1.magalog.net/clients/'.$this->property('domain').'?url=user%2F'.$this->property('domain');
				}
				break;
				
			case 'story':
			
				$key = 'styla_SEO_story_'.substr($this->page['param'], -6); // create cache key with story ID
			
				if($environment == 'live'){
					$url = 'http://seo.styla.com/clients/'.$this->property('domain').'?url=story%2F'.$this->page['param'];
				}
				else{
					$url = 'http://seoapistage1.magalog.net/clients/'.$this->property('domain').'?url=story%2F'.$this->page['param'];
				}
				break;
			
			case 'tag':
				
				$key = 'styla_SEO_tag_'.$this->page['param'];
			
				if($environment == 'live'){
					$url = 'http://seo.styla.com/clients/'.$this->property('domain').'?url=tag%2F'.$this->property('param');
				}
				else{
					$url = 'http://seoapistage1.magalog.net/clients/'.$this->property('domain').'?url=tag%2F'.$this->page['param'];
				}
				break;
			
			default: break;
		}
		
		$value = Cache::remember($key, $minutes, function() use($url){
		    return @file_get_contents($url);
		});
		
		/*
			// DEBUG
			$this->page['url'] = $url;
			$this->page['key'] = $key;
			$this->page['minutes'] = $minutes;
			$this->page['cache_env'] = $environment;
			$this->page['cache_type'] = $type;
		*/
		
		return $value;
		
	}
}