<?php
namespace App\Controller;

class gallery_flickr
{
    protected $args;

    function __construct($args)
    {
        foreach($args as $k =>$v){
            $this->$k = $v;
        }

        $this->gallery_title = ($this->gallery_title ? $this->gallery_title : 'Gallery');
        $this->page          = (isset($_GET['p']) && $_GET['p'] > 0 ? $_GET['p'] : 1);
        $this->per_page      = ($this->per_page ? $this->per_page : null);
    }

    public function getPhotoSets()
    {
        $sets = self::apiRequest('flickr.photosets.getList',array('user_id'=>$this->user_id,'page'=>$this->page,'per_page'=>$this->per_page));
        return $sets;
    }

    public function getPhotosInSet($photoset_id)
    {
        $photos = self::apiRequest('flickr.photosets.getPhotos',array('photoset_id'=>$photoset_id,'extras'=>'date_upload'));
        return $photos;
    }

    public function getUserId($username)
    {
        $id = self::apiRequest('flickr.people.findByUsername',array('username'=>$username));
        if($id['stat'] == 'ok'){
            return $id['user']['id'];
        }
        return $id;
    }

    public function getRecentPhotos($photos=5)
    {
        $photos = self::apiRequest('flickr.people.getPhotos',array('user_id'=>$this->user_id,'per_page'=>$photos));
        if($photos['stat'] == 'ok')
        {
            return $photos['photos']['photo'];
        }
        return false;
    }

    private function apiRequest($method,$params=array())
    {
        $cache_key = $params;

        $params = array('api_key'=>urlencode($this->api_key),'format'=>'php_serial') + $params;
        foreach($params as $k => $v){
            $encoded_params[] = urlencode($k).'='.urlencode($v);
        }

        $url = 'https://api.flickr.com/services/rest/?method='.$method.'&'.implode('&', $encoded_params);

        $response = self::getCache(implode('.', $cache_key)); // check for cached response
        if(!$response)
        {
            $response = @file_get_contents($url);
            self::setCache(implode('.', $cache_key),$response); // store response in cache
        }

        return unserialize($response);
    }

    private function getCache($key)
    {
        if(file_exists($this->cache['path'].'/'.$key) AND filemtime($this->cache['path'].'/'.$key) > (date("U") - (60 * $this->cache['time'])))
        {
            $cache = file($this->cache['path'].'/'.$key);
            return $cache[0];
        }

        return false;
    }

    private function setCache($key,$data)
    {
        if(isset($this->cache['path']) AND is_writeable($this->cache['path']))
        {
            $fp = fopen($this->cache['path'].'/'.$key, 'w');
            fwrite($fp, $data);
            fclose($fp);
        }
    }

    public function display()
    {
        if(isset($_GET['id'])){
            $return = self::displayPhotosInSet($_GET['id']);
        }
        else{$return = self::displayPhotoSets();}

        return (!$this->bootstrap ? '' : '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">').'
		'.$return;
    }


    public function displayPhotoSets()
    {
        $sets = self::getPhotoSets();
        if($sets['stat'] == 'ok' AND is_array($sets['photosets']['photoset']))
        {
            if($sets['photosets']['total'] > 0)
            {

                $display = '<div class="col-md-3 col-sm-12"><div class="cat-sidebar  py-2">';
                foreach($sets['photosets']['photoset'] as $set)
                {
                    $display .= '<div class="sidebar-head p-2 px-3">
										<a class="text-light text-decoration-none fcategory" dataid="'.$set['id'].'">
	      								<div>
	      									'.$set['title']['_content'].'
	      								</div>
	      							</a>
	      						</div>';

                }
                $display .= "</div></div>
                   <div class='col-md-9 col-sm-12 display_all_images'>
                                         
                   </div>
                   <div style='display: none' class='col-md-9 col-sm-12 d-flex justify-content-around galleryphoto'>
                      <img  class='w-75' />
                      <h5 class='ml-2'  id='photodetail'></h5>
                   </div>
                   ";
            }
            else{$display = self::alert('No photo sets to display','warning');}


            $return = '<div class="row">
  								'.$display.'
  							</div></div>'.self::pagination($sets['photosets']['page'],$sets['photosets']['pages']);
        }
        else
        {
            $return = '<div class="row">
							'.self::alert('Unable to get photo sets due to: '.$sets['message'],'danger').'
						</div>';
        }

        return self::breadcrumbs().$return;
    }

    public function displayAlbumPhotoSets(){
        $sets = self::getPhotoSets();
        if($sets['stat'] == 'ok' AND is_array($sets['photosets']['photoset']))
        {
            if($sets['photosets']['total'] > 0)
            {

                $display = '<div class=" myyhdbdufu">';
                foreach($sets['photosets']['photoset'] as $set)
                {
                    $display .= '<div>
										<a href="?id='.$set['id'].'">
	      								<div >
	      									'.$set['title']['_content'].'
	      								</div>
	      							</a>
	      						</div>';

                }
            }
            else{$display = self::alert('No photo sets to display','warning');}


            $return = '<div>
  								'.$display.'
  							</div></div>'.self::pagination($sets['photosets']['page'],$sets['photosets']['pages']);
        }
        else
        {
            $return = '<div class="row">
							'.self::alert('Unable to get photo sets due to: '.$sets['message'],'danger').'
						</div>';
        }

        return self::breadcrumbs().$return;
    }

    public function displayAlbumPhotosInSet($set_id)
    {
        //display album photos details
        $sets = self::getPhotosInSet($set_id);

        if($sets['stat'] == 'ok' AND is_array($sets['photoset']['photo']))
        {

            $display = '<div class="col-md-9" style="display: flex;margin: 10px">';
            $return = self::displayAlbumPhotoSets();
            echo $return;

            foreach($sets['photoset']['photo'] as $set)
            {
                $title = self::imageTitle($set['title']);
                $photoid = $set['id'];
                $display .= '<div class="col-md-4 col-sm-12 m-2" >
								<a class="displayimage" title="'.$title.'" id="'.$photoid.'">
      								  <img class="" dataImg="http://farm'.$set['farm'].'.staticflickr.com/'.$set['server'].'/'.$set['id'].'_'.$set['secret'].'_q.jpg" src="http://farm'.$set['farm'].'.staticflickr.com/'.$set['server'].'/'.$set['id'].'_'.$set['secret'].'_q.jpg" >
      							</a>
      						</div>';

            }

            $return = self::breadcrumbs(array('title'=>$sets['photoset']['title'])).'
						<div class="row" id="links">
  							'.$display.'
  						</div>
  						<div>
  						  <img class="gphoto" />
                        </div>
  												
						</script>';
            ?>
            </div>
            <?php
        }
        else
        {
            $return = self::breadcrumbs().'
						<div class="row">
							'.self::alert('Unable to get photo sets due to: '.$sets['message'],'danger').'
						</div>';
        }
        return $return;
    }

    public function displayPhotosInSet($set_id)
    {
        $sets = self::getPhotosInSet($set_id);

        if($sets['stat'] == 'ok' AND is_array($sets['photoset']['photo']))
        {

            $display = '<div class="row">';

            foreach($sets['photoset']['photo'] as $set)
            {
                $title = self::imageTitle($set['title']);
                $photoid = $set['id'];
                $display .= '<div class="col-md-4 col-sm-12 m-2">
								<a class="displayimage" title="'.$title.'" id="'.$photoid.'">
      								  <img class=" img-fluid w-100" src="http://farm'.$set['farm'].'.staticflickr.com/'.$set['server'].'/'.$set['id'].'_'.$set['secret'].'_q.jpg" >
      							</a>
      						</div>';

            }

            $return = self::breadcrumbs(array('title'=>$sets['photoset']['title'])).'
						<div  id="links">
  							'.$display.'
  						</div>
  						
						

						<!-- scripts -->
						'.(!$this->jquery ? '' : '<script src="https://code.jquery.com/jquery.js"></script>').'
						
					';
            ?>
            </div>
            <?php
        }
        else
        {
            $return = self::breadcrumbs().'
						<div class="row">
							'.self::alert('Unable to get photo sets due to: '.$sets['message'],'danger').'
						</div>';
        }
        return $return;
    }


    public function alert($message,$alert_type='danger')
    {
        return '<div class="alert alert-'.$alert_type.'">'.$message.'</div>';
    }


    private function breadcrumbs($crumbs=array())
    {
        $bc = '<li class="active albumtitle text-center" style="list-style: none">'.$this->gallery_title.'</a></li>';

        if(count($crumbs) > 0)
        {
            $bc = '
					<center><h3 class="">'.$crumbs['title'].' Photo Gallery</h3></center>';
        }

        return '<div class="col-md-12 p-0">
					<div class="">						
				   		'.$bc.'				   		
				   </div>';
    }


    function pagination($current,$pages)
    {
        if($pages == 1){return '';}

        if($current < $pages)
        {
            $prev = array('class'=>'','url'=>'?p='.($current - 1));
            $next = array('class'=>'','url'=>'?p='.($current + 1));

            if($current <= 1){
                $prev = array('class'=>'disabled','url'=>'#');
            }
        }
        elseif($current >= $pages)
        {
            if($current > $pages){$current = $pages + 1;} // force prev to link to actual last page
            $prev = array('class'=>'','url'=>'?p='.($current - 1));
            $next = array('class'=>'disabled','url'=>'#');
        }

        return '<ul class="pager">
						<li class="'.$prev['class'].'"><a href="'.$prev['url'].'">&laquo; Previous</a></li>
						<li class="'.$next['class'].'"><a href="'.$next['url'].'">Next &raquo;</a></li>
					</ul>';
    }


    private function imageTitle($title)
    {
        $image_prefix = array('dscn','dcim','cimg','dsc');
        foreach($image_prefix as $i)
        {
            if(0 === strpos($title, $i)){
                return '';
            }
        }
        return $title;
    }
}