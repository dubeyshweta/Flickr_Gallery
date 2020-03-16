<?php

namespace App\Controller;

require dirname(__DIR__).'/Classes/class.gallery_flickr.php';

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyController
{
    public function gallery()
    {
        $args = array(
            'api_key'       => 'aebc4f23c65b7f70c39f8aa7cebafff1',
            'user_id'       => '187415548@N07',
            'gallery_title' => 'Categories of Gallery',
            'gallery_url'   => '/',
            'assets_url'    => '/gallery',
            'cache'         => array('path'=>__DIR__.'/gallery/cache','time'=>30),
            'per_page'      => 24,
            'indicator'     => false,
            'jquery'        => true,
            'bootstrap'     => true,
        );
        $gallery = new gallery_flickr($args);
        $data = $gallery->display();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title></title>
            <meta name="viewport" content="width=device-width">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                .galleryMain{
                    height: 100%;
                    width: 100%;
                }
                .albumtitle{
                    margin: 10px 0;
                    padding: 15px;
                    list-style: none;
                    box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75);
                }
                .albumheading{
                    margin: 0;
                    padding: 0;
                }
                .cat-sidebar{
                 background: #273147;
                }
                .sidebar-head:hover{
                    background: #1c253d;
                }
                .sidebar-head a{
                    font-weight: 500;
                }
                .cat-sidebar {
                    height: 85vh;
                }
                @media (max-width: 768px) {
                    .cat-sidebar {
                        height: auto;
                    }
                }
            </style>
        </head>
        <body>
        <div class="container galleryMain">
           <?php
               echo $data;
               ?>

           <div class="row" style="margin: 10px;">
               <div>
                  <img id="photoid" class="photoinfo"/>
                  <div id="phototitle"></div>
               </div>
           </div>
        </div>
        </body>
        </html>
        <?php
        return new Response();
    }
    public function ajaxRequest(){
        ?>
            <div>
                <?php
                $args = array(
                    'api_key'       => 'aebc4f23c65b7f70c39f8aa7cebafff1',
                    'user_id'       => '187415548@N07',
                    'gallery_title' => 'Categories of Gallery',
                    'gallery_url'   => '/',
                    'assets_url'    => '/gallery',
                    'cache'         => array('path'=>__DIR__.'/gallery/cache','time'=>30),
                    'per_page'      => 24,
                    'indicator'     => false,
                    'jquery'        => true,
                    'bootstrap'     => true,
                );
                $gallery = new gallery_flickr($args);
                    $dataId = $_REQUEST['id'];
                    $functionCall = $gallery->displayPhotosInSet($dataId);
                    print_r($functionCall);
                ?>
            </div>
        <?php
        return new Response(

        );
    }
}
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<script>
    $(document).ready(function ()
    {
        $('.displayimage').click(function () {
            var imgdata = $(this).find('img').attr('src');

            var id = $(this).attr('id');

            var photourl = "https://www.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=aebc4f23c65b7f70c39f8aa7cebafff1&photo_id="+id+"&format=json&nojsoncallback=1)";
            console.log("Photo URL :"+photourl);

            $.ajax({
                url: photourl,
                type: "GET",
                dataType: 'json',
                success:function (res) {
                    if(res['stat'] == "ok")
                    {
                        $('.display_all_images').hide();
                        $('.galleryphoto').show();
                        $('.galleryphoto').find('img').attr('src',imgdata);
                        $('#photodetail').text(res.photo['title']['_content']);
                    }
                }
            });
            return false;
        });
        $('.fcategory').click(function(e){
            $('.galleryphoto').hide();
            e.preventDefault();
            var Id = jQuery(this).attr("dataid");
            $.ajax({
                url: "/ajaxrequest",
                type: "POST",
                data: {
                    'id': Id
                },
                success:function (res) {
                        $('.display_all_images').html(res);
                        $('.display_all_images').show();
                        $('.galleryphoto').find('img').attr('src','');
                        $('#photodetail').text('');

                }
            });
        })
    })
</script>
