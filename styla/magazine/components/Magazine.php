<?php namespace Styla\Magazine\Components;

use Cms\Classes\ComponentBase;
use Redirect;
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
                'type'              => 'string',
                'showExternalParam' => false
            ],
            'cdnserver' => [
                'description'       => 'Server that delivers the script and styles for the magazine.',
                'title'             => 'CDN server',
                'default'           => 'http://cdn.styla.com/',
                'type'              => 'string',
                'validationPattern' => '(https?:\/\/)([\da-z\.-]+)([\/\w \.-]*)*\/',
                'validationMessage' => 'URL must begin with http(s):// and end with a trailing slash',
                'required'			=> true,
                'showExternalParam' => false
            ],
            'seoserver' => [
                'description'       => 'Server that delivers SEO information for the magazine.',
                'title'             => 'SEO server',
                'default'           => 'http://seo.styla.com/clients/',
                'type'              => 'string',
                'validationPattern' => '(https?:\/\/)([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/',
                'validationMessage' => 'URL must begin with http(s):// and end with a trailing slash',
                'required'			=> true,
                'showExternalParam' => false
            ],
            'versionserver' => [
	        	'description'		=> 'Server that delivers the current script and styles version number',
	        	'title'				=> 'Version server',
	        	'default'			=> 'http://live.styla.com/api/version/',
	        	'type'				=> 'string',
	        	'validationPattern' => '(https?:\/\/)([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/',
                'validationMessage' => 'URL must begin with http(s):// and end with a trailing slash',
                'required'			=> true,
                'showExternalParam' => false
            ],
            'feedlimit' => [
                'description'       => 'Numbers of pages to be displayed.',
                'title'             => 'Feedlimit (optional)',
                'default'           => '',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
				'validationMessage' => 'The Feedlimit property can contain only numeric symbols',
				'showExternalParam' => false
            ],
            'tag' => [
                'description'       => 'Display only stories with the specified Tag.',
                'title'             => 'Tag (optional)',
                'default'           => '',
                'type'              => 'string',
                'showExternalParam' => false
            ],
            'caching' => [
                'description'       => 'Enables caching of SEO and version information.',
                'title'             => 'Enable Caching',
                'default'           => true,
                'type'              => 'checkbox',
                'showExternalParam' => false
            ],
            'debug' => [
                'description'       => 'Displays DEBUG information above the magazine.',
                'title'             => 'Debug',
                'default'           => false,
                'type'              => 'checkbox',
                'showExternalParam' => false
            ]
        ];
    }

    public function onRun()
    {
	    $version = ''; // version of the script and styles received from CDN
	    $feedlimit = $this->property('feedlimit');
	    $tag = $this->property('tag');

        $this->page['caching'] = $this->property('caching');

	    // --- Set file_get_contents Timeout to 10s -------
	    ini_set('default_socket_timeout', 10);

        // --- Get CDN Version ----------------------------

        $version = $this->fetchAndRememberCdnVersion();

        // --- Create CDN code snippets -------------------

        $this->page['Styles'] = '<link rel="stylesheet" type="text/css" href="'.$this->property('cdnserver').'styles/clients/'.$this->property('domain').'.css?v='.$version.'">';

        $extras = (!empty($feedlimit) ? ' data-feedlimit="'.$feedlimit.'"' : '').
        		  (!empty($tag) ? ' data-tag="'.$tag.'"' : '');

        $this->page['Script'] = '<script async src="'.$this->property('cdnserver').'scripts/clients/'.$this->property('domain').'.js?v='.$version.'"'.$extras.'></script>';

        // --- Fetch SEO content --------------------------

        $param = $this->param('param') ? $this->param('param') : '/';
    	$seo = $this->fetchAndRememberSEO( $param );

    	if(is_object( $seo )){
            if(isset($seo->html->head)) $this->page['SEO_head'] = $seo->html->head;
			if(isset($seo->html->body)) $this->page['SEO_body'] = $seo->html->body;
    	}

        // Debug infos
        $this->page['debug'] = $this->property('debug');
        if($this->property('debug')){
	        $this->page['domain'] = $this->property('domain');            		# magazine domain name
	        $this->page['param'] = $param;                 						# current url parameter
	        $this->page['feedlimit'] = $this->property('feedlimit');      		# enable/disable feedlimit
	        $this->page['tag'] = $this->property('tag');      					# Tag
	        $this->page['seoserver'] = $this->property('seoserver');      		# URL to SEO server
	        $this->page['cdnserver'] = $this->property('cdnserver');      		# URL to CDN server
	        $this->page['versionserver'] = $this->property('versionserver');    # URL to Version server
            $this->page['caching'] = $this->property('caching');                # Cache disabled
        }
    }

    /***************************************
	 * C A C H E  F U N C T I O N S
	 ***************************************/

	// Fetch and remember SEO information
	public function fetchAndRememberSEO( $key ){

		if($this->property('debug')){
			$this->page['url'] = $this->property('seoserver').$this->property('domain').'?url='.$key;
			$this->page['key'] = 'styla_SEO_'.$key;
		}

		// Check if SEO data is already in cache, if yes, return the cached data
		if(Cache::has('styla_SEO_'.$key) && $this->property('caching')){
			if($this->property('debug')){
				$this->page['readFromServer'] = 'false';
			}

			return Cache::get('styla_SEO_'.$key);
		}
		else{
			if($this->property('debug')){
				$this->page['readFromServer'] = 'true';
			}

			// Fetch data from server
			$data = @file_get_contents($this->property('seoserver').$this->property('domain').'?url='.$key);

			// Check if any data was received
			if($data != FALSE){
				// JSON decode
            	$json = json_decode($data);

            	// Check if json has status code
				if(!isset($json->status)){
	            	return 'Styla Plugin: No status code in SEO response.';
            	}

            	// check if response code is 2XX
            	if(substr((string)$json->status, 0, 1) == '2'){

	            	// if no expire is present, default to 60min
	            	$expire = isset($json->expire) ? $json->expire / 60 : 60;

	            	// Save JSON to Cache
					Cache::put('styla_SEO_'.$key, $json, $expire);

					if($this->property('debug')){
			            $this->page['status'] = $json->status; 	# SEO response status code
			            $this->page['duration'] = $expire;   	# Caching duration
		            }

					// Return the JSON
					return $json;
				}
				else{
		            return 'Styla Plugin: Status code is not 2XX: '.$json->status;
	            }
	        }
	        else{
		        return 'Styla Plugin: No data received from SEO API.';
	        }
		}
	}

	// Fetch and remember SEO information
	public function fetchAndRememberCdnVersion(){

        if($this->property('caching')){
    		$version = Cache::remember('CDN_version', 60, function(){
    		    return @file_get_contents($this->property('versionserver').$this->property('domain'));
    		});
        }
        else{
            $version = @file_get_contents($this->property('versionserver').$this->property('domain'));
        }

		if($this->property('debug')){
			$this->page['version'] = $version;
		}

		return $version;
	}
}
