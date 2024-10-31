<?php
function tagmanage_shouquan(){
     if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
            $key = sanitize_text_field($_POST['key']);
            $data =  tagmanage_url();
            $url1 = sanitize_url($_SERVER['SERVER_NAME']);
            $url = 'https://www.rbzzz.com/api/money/log2?url='.$data.'&url1='.$url1.'&key='.$key;
            $defaults = array(
                'timeout' => 120,
                'connecttimeout'=>120,
                'redirection' => 3,
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
                'sslverify' => FALSE,
            );
            $result = wp_remote_get($url,$defaults);
            if(!is_wp_error($result)){
                $content = wp_remote_retrieve_body($result);
                if($content){
                    $Tag_manage_key = get_option('tag_manage_key');
                    if($Tag_manage_key!==false){
                        update_option('tag_manage_key',$key);
                    }else{
                        add_option('tag_manage_key',$key);
                    }
                    echo wp_json_encode(['code'=>1]);exit;
                }else{
                     echo wp_json_encode(['code'=>0]);exit;
                }
            }else{
                echo wp_json_encode(['code'=>0]);exit;
            }
        
            
        }
}
function tagmanage_post(){
   global $wpdb;
    $pay = tagmanage_paymoney('/api/index/pay_money');
    if($pay['msg']==1){
        if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
            $tag['link']=(int)$_POST['link'];       
            $tag['auto']=(int)$_POST['auto'];       
            $tag['bold'] = (int)$_POST['bold'];
            $tag['color'] = sanitize_text_field($_POST['color']);
            $tag['num'] = (int)$_POST['num'];
            $tag['nlnum'] = (int)$_POST['nlnum'];
            $tag['pp'] = (int)$_POST['pp'];
            $tag['is_rem'] = (int)$_POST['is_rem'];
            $tags= explode(',',sanitize_text_field($_POST['bqgl']));
            $tag['bqgl'] = [];
            foreach($tags as $key=>$val){
                if($val){
                    $tag['bqgl'][] = (int)$val;
                }
            }
            if(isset($_POST['hremove'])){
                $tag['hremove'] = (int)$_POST['hremove'];
            }else{
                $tag['hremove'] = 0;
            }
            $baiduseo_tag_manage = get_option('Tag_manage_link');
            if($baiduseo_tag_manage){
                update_option('Tag_manage_link',$tag);
            }else{
                add_option('Tag_manage_link',$tag);
            }
            echo wp_json_encode(['code'=>1,'msg'=>'保存成功']);exit;
        }else{
            echo wp_json_encode(['code'=>0,'msg'=>'保存失败']);exit;
        }
            
    }else{
        echo wp_json_encode(['code'=>0,'msg'=>'请先授权']);exit;
    }
}
function tagmanage_tag_add(){
    global $wpdb;
    set_time_limit(0);
    ini_set('memory_limit','-1');
    if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
     $pay = tagmanage_paymoney('/api/index/pay_money');
    if($pay['msg']==1){
   
    $content = explode("\n",sanitize_textarea_field($_POST['content']));
                
if($content){
   
    global $wpdb;
            foreach($content as $key=>$val){
                $tag = explode(',',$val);
                if(isset($tag[1])){
                    $res = $wpdb->get_results($wpdb->prepare('select id from '.$wpdb->prefix . 'baiduseo_neilian keywords=%s',$tag[0]),ARRAY_A);
                    if($res){
                        
                    }else{
                        $res = $wpdb->insert($wpdb->prefix."baiduseo_neilian",['keywords'=>$tag[0],'link'=>$tag[1],]);
                    }
                }else{
                   $terms = $wpdb->get_results($wpdb->prepare('select a.* from '.$wpdb->prefix . 'terms as a left join '.$wpdb->prefix . 'term_taxonomy as b on a.term_id=b.term_id   where b.taxonomy="post_tag" and a.name=%s',$tag[0]),ARRAY_A);
                    if(!$terms){
                        $res = $wpdb->insert($wpdb->prefix."terms",['name'=>$tag[0]]);
                        
                         $id = $wpdb->insert_id;
                
                        $wpdb->update($wpdb->prefix . 'terms',['slug'=>$id],['term_id'=>$id]);
                        $wpdb->insert($wpdb->prefix."term_taxonomy",['term_id'=>$id,'taxonomy'=>'post_tag']);
                    
                        $id_1 = $wpdb->insert_id;
                        $baiduseo_tag_manage = get_option('Tag_manage_link');
                        if($baiduseo_tag_manage){
                            if(isset($baiduseo_tag_manage['hremove']) && $baiduseo_tag_manage['hremove']==1){
                                if(isset($baiduseo_tag_manage['auto']) && $baiduseo_tag_manage['auto']){
                                    $article = $wpdb->get_results('select * from '.$wpdb->prefix . 'posts where  post_status="publish" and post_type="post" order by ID desc limit 10000',ARRAY_A);
                                    if(!isset($baiduseo_tag_manage['num']) || !$baiduseo_tag_manage['num'] || $baiduseo_tag_manage['num']==11){
                                        
                                        foreach($article as $k=>$v){
                                           
                                            if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($tag[0]).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                            {
                                                $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                            
                                                $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);       
                                                $count = $term_taxonomy[0]['count']+1;
                                                $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                            }
                                        }
                                    }else{
                                        foreach($article as $k=>$v){
                                           $shu = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix .'term_relationships as a left join '.$wpdb->prefix .'term_taxonomy as b on a.term_taxonomy_id=b.term_taxonomy_id where b.taxonomy="post_tag" and a.object_id=%d' ,$v['ID']));
                                            if($shu>=$baiduseo_tag_manage['num']){
                                                break;
                                            }else{
                                                
                                                if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($tag[0]).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                                {
                                                    $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                    $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);
                                                            
                                                    $count = $term_taxonomy[0]['count']+1;
                                                    $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }else{
                                if(isset($baiduseo_tag_manage['auto']) && $baiduseo_tag_manage['auto']){
                                    $article = $wpdb->get_results('select * from '.$wpdb->prefix . 'posts where  post_status="publish" and post_type="post" order by ID desc limit 10000',ARRAY_A);
                                    if(!isset($baiduseo_tag_manage['num']) || !$baiduseo_tag_manage['num'] || $baiduseo_tag_manage['num']==11){
                                        
                                        foreach($article as $k=>$v){
                                           
                                            if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($tag[0]).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                            {
                                                $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                            
                                                $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);       
                                                $count = $term_taxonomy[0]['count']+1;
                                                $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                            }
                                        }
                                    }else{
                                        foreach($article as $k=>$v){
                                           $shu = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix .'term_relationships as a left join '.$wpdb->prefix .'term_taxonomy as b on a.term_taxonomy_id=b.term_taxonomy_id where b.taxonomy="post_tag" and a.object_id=%d' ,$v['ID']));
                                            if($shu>=$baiduseo_tag_manage['num']){
                                                break;
                                            }else{
                                                
                                                if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($tag[0]).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                                {
                                                    $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                    $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);
                                                            
                                                    $count = $term_taxonomy[0]['count']+1;
                                                    $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                   
                    
                }
            }
        }else{
            echo wp_json_encode(['code'=>0,'msg'=>'添加失败']);exit;
        }
    
        echo wp_json_encode(['code'=>1,'msg'=>'添加成功']);exit;
    }else{
        echo wp_json_encode(['code'=>0,'msg'=>'添加失败']);exit;
    }
    }

}
function tagmanage_xunhuan(){
    set_time_limit(0);
    ini_set('memory_limit','-1');
    global $wpdb;
    if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
    $pay = tagmanage_paymoney('/api/index/pay_money');
    if($pay['msg']==1){
    $num = (int)$_POST['num'];
    $page = (int)$_POST['page'];
    $tag_num = (int)$_POST['tag_num'];
    $article = $wpdb->get_results('select * from '.$wpdb->prefix . 'posts where post_status="publish" and post_type="post"',ARRAY_A);
    $total = count($article);
    $start = ($page-1)*$num;
    $list = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'posts where post_status="publish" and post_type="post" limit %d,%d',$start,$num),ARRAY_A);
    if(isset($list[0]) && !empty($list[0])){
        foreach($list as $key=>$val){
            // var_dump($val['ID'].',');
            $tag_article = $wpdb->get_results($wpdb->prepare('select a.* from '.$wpdb->prefix . 'term_relationships as a left join '.$wpdb->prefix.'term_taxonomy as b on a.term_taxonomy_id=b.term_taxonomy_id where  a.object_id=%d and b.taxonomy="post_tag"',$val['ID']),ARRAY_A);
            
            if(!empty($tag_article)){
                $count = count($tag_article);
            }else{
                $count = 0;
            }
            if($count==$tag_num){
               
            }elseif($count<$tag_num){
                
                $tags=$wpdb->get_results('select * from '.$wpdb->prefix . 'terms',ARRAY_A);
                $nos =0;
                foreach($tags as $k=>$v){
                    
                     $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where term_id=  %d and  taxonomy="post_tag"',$v['term_id']),ARRAY_A);
                  if(!empty($term_taxonomy)){
                   
                       $res = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where object_id=  %d and term_taxonomy_id=%d',$val['ID'],$term_taxonomy[0]['term_taxonomy_id']),ARRAY_A);
                        
                        if(empty($res)){
                           
                            // var_dump($tag_num-$count);
                            if($nos<($tag_num-$count)){
                                
                                $baiduseo_tag_manage = get_option('Tag_manage_link');
                                 if(isset($baiduseo_tag_manage['hremove']) && $baiduseo_tag_manage['hremove']==1){
                                      if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($v['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',get_post($val['ID'])->post_content,$matches))
                                        {
                                            
                                            $re = $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$val['ID'],'term_taxonomy_id'=>$term_taxonomy[0]['term_taxonomy_id']]);
                                            if($re){
                                                ++$nos;
                                            }
                                            $counts = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where  term_taxonomy_id=%d',$term_taxonomy[0]['term_taxonomy_id']));
            
                                            $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$counts],['term_taxonomy_id'=>$term_taxonomy[0]['term_taxonomy_id']]);
                                            
                                            
                                        }
                                 }else{
                                       if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($v['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',get_post($val['ID'])->post_content,$matches))
                                        {
                                            
                                            $re = $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$val['ID'],'term_taxonomy_id'=>$term_taxonomy[0]['term_taxonomy_id']]);
                                            if($re){
                                                ++$nos;
                                            }
                                            $counts = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where  term_taxonomy_id=%d',$term_taxonomy[0]['term_taxonomy_id']));
            
                                            $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$counts],['term_taxonomy_id'=>$term_taxonomy[0]['term_taxonomy_id']]);
                                            
                                            
                                        }
                                 }
                              
                            }
                        }
                    }
                }
            }elseif($count>$tag_num){
               
                $no = 0;
                foreach($tag_article as $k=>$v){
             
                    if($no<($count-$tag_num)){
                        
                         $re = $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "term_relationships where object_id=  %d and term_taxonomy_id=%d",$v['object_id'],$v['term_taxonomy_id']),ARRAY_A);
                        if($re){
                            ++$no;
                        }
                       $counts = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where  term_taxonomy_id=%d',$v['term_taxonomy_id']));
                        $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$counts],['term_taxonomy_id'=>$v['term_taxonomy_id']]);
                        
                    }
                    
                }
                
            }
        }

        echo wp_json_encode(['num'=>$num,'percent'=>round(100*($start+count($list))/$total,2).'%','page'=>$page,'tag_num'=>$tag_num,'code'=>1,'total'=>$total,'start'=>$start,'l'=>count($list)]);exit;
    }else{
        echo wp_json_encode(['msg'=>"操作完成",'code'=>2]);exit;
    }
    }else{
        echo wp_json_encode(['msg'=>"请先授权",'code'=>0]);exit;
    }
    }
}
function tagmanage_neilian(){
                   
    global $wpdb;
    if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
        $id = (int)$_POST['id'];
        if(isset($_POST['keywords'])){
            $data['keywords'] = sanitize_text_field($_POST['keywords']);
        }
        if(isset($_POST['link'])){
            $data['link'] = sanitize_text_field($_POST['link']);
        }
        if(isset($_POST['target'])){
            $data['target'] = (int)$_POST['target'];
        }
        if(isset($_POST['sort'])){
            $data['sort'] = (int)$_POST['sort'];
        }
        if(isset($_POST['nofollow'])){
            $data['nofollow'] = (int)$_POST['nofollow'];
        }
        $res = $wpdb->update($wpdb->prefix . 'baiduseo_neilian',$data,['id'=>(int)$id]);
        echo wp_json_encode(['code'=>1]);exit;
    }
     echo wp_json_encode(['code'=>0]);exit;
}
function tagmanage_neilian_delete(){
    if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
        $dele = sanitize_text_field($_POST['dele']);
        $dele = explode(',',$dele);
        if(!empty($dele) && is_array($dele)){
            $dele = array_map('intval',wp_unslash( (array)$dele));
            global $wpdb;
            foreach($dele as $key=>$val){
             $res = $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "baiduseo_neilian where id=  %d",(int)$val));
            }
            echo wp_json_encode(['code'=>1]);exit;
        }
    }
     echo wp_json_encode(['code'=>0]);exit;
}
function tagmanage_get_neilian(){
    if(isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
        global $wpdb;
        if(isset($_POST['keywords'])){
            $search = sanitize_text_field($_POST['keywords']);
            $post1 = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where keywords like %s order by id desc ",'%'.$search.'%'),ARRAY_A);
            echo wp_json_encode(['code'=>1,'msg'=>'','count'=>0,'data'=>$post1]);exit; 
        }else{
            $p1 = isset($_POST['pages'])?(int)$_POST['pages']:1;
            $start1 = ($p1-1)*20;
            $count = $wpdb->query("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian  ",ARRAY_A);
            $post1 = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian order by id desc limit %d,20",$start1),ARRAY_A);
            echo wp_json_encode(['code'=>1,'msg'=>'','count'=>ceil($count/20),'data'=>$post1,'totalnum'=>$count]);exit;
        }
    }
}
 function tagmanage_neilian_delete_all(){
     if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
            global $wpdb;
            $res = $wpdb->query("DELETE FROM " . $wpdb->prefix . "baiduseo_neilian ");
            echo wp_json_encode(['code'=>1]);exit;
        }
        
        echo wp_json_encode(['code'=>0]);exit;
 }
 function  tagmanage_reci(){
    if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
           
            $type = (int)$_POST['type'];
            $keyword = sanitize_text_field($_POST['keyword']);
            $result = wp_remote_post('https://www.rbzzz.com/api/tag/index',['body'=>['type'=>$type,'keyword'=>$keyword,'url'=> tagmanage_url(),'status'=>3]]);
            
            if(!is_wp_error($result)){
                $result = wp_remote_retrieve_body($result);
                
                $result = json_decode($result,true);
                
                if($result['code']){
                    $msg = array_map('sanitize_text_field',$result['msg']);
                    
                    echo wp_json_encode(['msg'=>$msg,'code'=>1]);exit;
                }else{
                    echo wp_json_encode(['msg'=>'请先授权','code'=>0]);exit;
                }
            }else{
                
            }
        }
}

function tagmanage_add_tag(){
     set_time_limit(0);
        ini_set('memory_limit','-1');
        if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
            $keyword = sanitize_text_field($_POST['keyword']);
            $type = (int)$_POST['type'];
            global $wpdb;
             $pay = tagmanage_paymoney('/api/index/pay_money');
            if($pay['msg']==1){
            if($type==1){
                $terms = $wpdb->get_results($wpdb->prepare('select a.* from '.$wpdb->prefix . 'terms as a left join '.$wpdb->prefix . 'term_taxonomy as b on a.term_id=b.term_id   where b.taxonomy="post_tag" and a.name=%s',$keyword),ARRAY_A);
                if(!$terms){
                    $res = $wpdb->insert($wpdb->prefix."terms",['name'=>$keyword]);
                    $id = $wpdb->insert_id;
                    $wpdb->update($wpdb->prefix . 'terms',['slug'=>$id],['term_id'=>$id]);
                    $wpdb->insert($wpdb->prefix."term_taxonomy",['term_id'=>$id,'taxonomy'=>'post_tag']);
                    $id_1 = $wpdb->insert_id;
                    $baiduseo_tag_manage = get_option('Tag_manage_link');
                    if($baiduseo_tag_manage){
                        if(isset($baiduseo_tag_manage['auto']) && $baiduseo_tag_manage['auto']){
                            $article = $wpdb->get_results('select * from '.$wpdb->prefix . 'posts where  post_status="publish" and post_type="post" order by ID desc limit 1000',ARRAY_A);
                            if(!isset($baiduseo_tag_manage['num']) || !$baiduseo_tag_manage['num'] || $baiduseo_tag_manage['num']==11){
                                
                                foreach($article as $k=>$v){
                                   if(isset($baiduseo_tag_manage['hremove']) && $baiduseo_tag_manage['hremove']==1){
                                        if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($keyword).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                        {
                                            $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                            $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$id_1),ARRAY_A);
                                            $count = $term_taxonomy[0]['count']+1;
                                            $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                        }
                                   }else{
                                    if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($keyword).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                    {
                                        $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                        $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$id_1),ARRAY_A);
                                        $count = $term_taxonomy[0]['count']+1;
                                        $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                    }
                                   }
                                }
                            }else{
                                foreach($article as $k=>$v){
                                    $shu = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix .'term_relationships as a left join '.$wpdb->prefix .'term_taxonomy as b on a.term_taxonomy_id=b.term_taxonomy_id where b.taxonomy="post_tag" and a.object_id=%d' ,$v['ID']));
                                    if($shu>=$baiduseo_tag_manage['num']){
                                        break;
                                    }else{
                                        if(isset($baiduseo_tag_manage['hremove']) && $baiduseo_tag_manage['hremove']==1){
                                            if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($keyword).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                            {
                                                $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);
                                                        
                                                $count = $term_taxonomy[0]['count']+1;
                                                $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                            }
                                        }else{
                                            if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($keyword).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                            {
                                                $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);
                                                        
                                                $count = $term_taxonomy[0]['count']+1;
                                                $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif($type==2){
                 $post1 = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where keywords =%s ",$keyword),ARRAY_A);
                if(empty($post1)){
                 $wpdb->insert($wpdb->prefix."baiduseo_neilian",['keywords'=>$keyword,'link'=>'/',]);
                }
               
            }
            }else{
                 echo wp_json_encode(['msg'=>'请先授权','code'=>0]);
                exit;
            }
        }
        echo wp_json_encode(['msg'=>'导入成功','code'=>1]);
                exit;
}
function tagmanage_enqueue($hook){
    if( 'toplevel_page_tagmanage' != $hook ) return;
     require plugin_dir_path( __FILE__ ) . 'assets.php';//公用资源
    foreach($assets as $key=>$val){
        if($val['type']==1){
             wp_enqueue_style( $val['name'],  plugin_dir_url( __FILE__ ).$val['url'],false,'','all');
        }elseif($val['type']==2){
           
            wp_enqueue_script( $val['name'], plugin_dir_url( __FILE__ ).$val['url'], '', '', true);
            
        }
       
    }
    wp_register_script('tagmanage.js', false, null, false);
    wp_enqueue_script('tagmanage.js');
    wp_add_inline_script('tagmanage.js', 'var tagmanage_wztkj_url="'.plugins_url('nleilian-guanjc').'/",tagmanage_nonce="'. wp_create_nonce('tagmanage').'",tagmanage_ajax="'.esc_url(admin_url('admin-ajax.php')).'",tagmanage_tag ="'.esc_url(admin_url('edit-tags.php?taxonomy=post_tag')).'";', 'before');
    
}
function tagmanage_5118(){
    
    if(isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
            $name = sanitize_text_field($_POST['name']);
            $defaults = array(
                'timeout' => 4000,
                'connecttimeout'=>4000,
                'redirection' => 3,
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
                'sslverify' => FALSE,
            );
            $url = 'http://wp.seohnzz.com/api/rank/word_vip?keywords='.$name;
            $result = wp_remote_get($url,$defaults);
            if(!is_wp_error($result)){
                $level = wp_remote_retrieve_body($result);
                echo wp_json_encode(['data'=>$level,'code'=>0]);
                exit;
                
            }
    }
    echo wp_json_encode(['msg'=>'','code'=>0]);
                exit;
}
function tagmanage_5118_daochu(){
    if(isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
            global $wpdb;
            $keywords = sanitize_text_field($_POST['keywords']);
            $total = (int)$_POST['total'];
            $long = (int)$_POST['long'];
            $collect = (int)$_POST['collect'];
            $bidword = (int)$_POST['bidword'];
            $defaults = array(
                'timeout' => 4000,
                'connecttimeout'=>4000,
                'redirection' => 3,
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
                'sslverify' => FALSE,
            );
            $data =  $siteurl = trim(get_option('siteurl'),'/');
            $url = 'http://wp.seohnzz.com/api/rank/daochu?keywords='.$keywords.'&total='.$total.'&long='.$long.'&collect='.$collect.'&bidword='.$bidword.'&url='.$data.'&type=3';
            $result = wp_remote_get($url,$defaults);
            if(!is_wp_error($result)){
                $level = wp_remote_retrieve_body($result);
                $level = json_decode($level,true);
                
                if(isset($level['code']) && $level['code']==1){
                    $res = $wpdb->insert($wpdb->prefix."baiduseo_long",['keywords'=>$keywords,'total'=>$total,'longs'=>$long,'collect'=>$collect,'bidword'=>$bidword]);
                    echo wp_json_encode(['code'=>1,'msg'=>'申请成功，请等待响应！']);exit;
                }elseif(isset($level['code']) && $level['code']==2){
                    echo wp_json_encode(['code'=>0,'msg'=>'申请失败，积分不足']);exit;
                }
            }else{
                echo wp_json_encode(['code'=>0,'msg'=>'申请失败，请稍后重试！']);exit;
            }
        }
        echo wp_json_encode(['code'=>0,'msg'=>'申请失败，请稍后重试！']);exit;
}
function tagmanage_toplevelpage() {
    echo "<div id='tagmanage_wztkj-app'></div>";
}
function tagmanage_tongji(){
    $tagmanage_tongji = get_option('tagmanage_tongji');
    if(!$tagmanage_tongji || (isset($tagmanage_tongji) && $tagmanage_tongji['time']<time()) ){
        $wp_version =  get_bloginfo('version');
        $data =  tagmanage_url();
        $url = "http://wp.seohnzz.com/api/tagmanage/index?url={$data}&type=3&url1=".md5($data.'seohnzz.com')."&theme_version=1.0.1&php_version=".PHP_VERSION."&wp_version={$wp_version}";
        $defaults = array(
            'timeout' => 120,
            'connecttimeout'=>120,
            'redirection' => 3,
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
            'sslverify' => FALSE,
        );
        $result = wp_remote_get($url,$defaults);
        if($tagmanage_tongji!==false){
            update_option('tagmanage_tongji',['time'=>time()+7*3600*24]);
        }else{
            add_option('tagmanage_tongji',['time'=>time()+7*3600*24]);
        }
    }
}
tagmanage_tongji();
function tagmanage_articlepublish($post_ID){
     global $wpdb;
     //自动关联标签
    $tagmanage_tag_manage = get_option('Tag_manage_link');
    
    if($tagmanage_tag_manage){
        if(isset($tagmanage_tag_manage['auto']) && $tagmanage_tag_manage['auto']){
            if(!isset($tagmanage_tag_manage['num']) || !$tagmanage_tag_manage['num'] || $tagmanage_tag_manage['num']==11){
                   if(isset($tagmanage_tag_manage['pp']) && $tagmanage_tag_manage['pp']==1){
                            
                        $tags=$wpdb->get_results('select * from '.$wpdb->prefix . 'terms ORDER BY LENGTH(name) DESC',ARRAY_A);
                    }elseif(isset($tagmanage_tag_manage['pp']) && $tagmanage_tag_manage['pp']==2){
                        $tags=$wpdb->get_results('select * from '.$wpdb->prefix . 'terms ORDER BY LENGTH(name) ASC',ARRAY_A);
                    }else{
                        $tags=$wpdb->get_results('select * from '.$wpdb->prefix . 'terms',ARRAY_A);
                    }
            foreach($tags as $k=>$v){
                 if(isset($tagmanage_tag_manage['hremove']) && $tagmanage_tag_manage['hremove']==1){
                if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($v['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',get_post($post_ID)->post_content,$matches))
                {
                    $res = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where taxonomy="post_tag" and term_id=%d',$v['term_id']),ARRAY_A);
                    
                    if($res){
                        $re = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where object_id=%d and term_taxonomy_id=%d',$post_ID,$res[0]['term_taxonomy_id']),ARRAY_A);
                        
                        if(!$re){
                            
                            $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$post_ID,'term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                            
                            $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$res[0]['term_taxonomy_id']),ARRAY_A);
                        
                            $count = $term_taxonomy[0]['count']+1;
                            $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                        }
                    }
                }
                 }else{
                      if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($v['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',get_post($post_ID)->post_content,$matches))
                {
                    $res = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where taxonomy="post_tag" and term_id=%d',$v['term_id']),ARRAY_A);
                    
                    if($res){
                        $re = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where object_id=%d and term_taxonomy_id=%d',$post_ID,$res[0]['term_taxonomy_id']),ARRAY_A);
                        
                        if(!$re){
                            
                            $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$post_ID,'term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                            
                            $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$res[0]['term_taxonomy_id']),ARRAY_A);
                        
                            $count = $term_taxonomy[0]['count']+1;
                            $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                        }
                    }
                }
                 }
            }
            }else{
                $shu = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix .'term_relationships as a left join '.$wpdb->prefix .'term_taxonomy as b on a.term_taxonomy_id=b.term_taxonomy_id where b.taxonomy="post_tag" and a.object_id=%d' ,$post_ID));
                if($shu<$tagmanage_tag_manage['num']){
                     if(isset($tagmanage_tag_manage['pp']) && $tagmanage_tag_manage['pp']==1){
                            
                        $tags=$wpdb->get_results('select * from '.$wpdb->prefix . 'terms ORDER BY LENGTH(name) DESC',ARRAY_A);
                    }elseif(isset($tagmanage_tag_manage['pp']) && $tagmanage_tag_manage['pp']==2){
                        $tags=$wpdb->get_results('select * from '.$wpdb->prefix . 'terms ORDER BY LENGTH(name) ASC',ARRAY_A);
                    }else{
                        $tags=$wpdb->get_results('select * from '.$wpdb->prefix . 'terms',ARRAY_A);
                    }
                    foreach($tags as $k=>$v){
                        
                        $shu = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix .'term_relationships as a left join '.$wpdb->prefix .'term_taxonomy as b on a.term_taxonomy_id=b.term_taxonomy_id where b.taxonomy="post_tag" and a.object_id=%d' ,$post_ID));
                       
                        if($shu<$tagmanage_tag_manage['num']){
                             if(isset($tagmanage_tag_manage['hremove']) && $tagmanage_tag_manage['hremove']==1){
                                   if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($v['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',get_post($post_ID)->post_content,$matches))
                            {
                                $res = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where taxonomy="post_tag" and term_id=%d' ,$v['term_id']),ARRAY_A);
                                if($res){
                                        $re = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where object_id=%d and term_taxonomy_id=%d',$post_ID,$res[0]['term_taxonomy_id']),ARRAY_A);
                                    
                                    if(!$re){
                                        
                                        $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$post_ID,'term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                                        $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$res[0]['term_taxonomy_id']),ARRAY_A);
                                    
                                        $count = $term_taxonomy[0]['count']+1;
                                        $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                                    }
                                }
                            
                        }
                             }else{
                                   if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($v['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',get_post($post_ID)->post_content,$matches))
                            {
                                $res = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where taxonomy="post_tag" and term_id=%d' ,$v['term_id']),ARRAY_A);
                                if($res){
                                        $re = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_relationships where object_id=%d and term_taxonomy_id=%d',$post_ID,$res[0]['term_taxonomy_id']),ARRAY_A);
                                    
                                    if(!$re){
                                        
                                        $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$post_ID,'term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                                        $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$res[0]['term_taxonomy_id']),ARRAY_A);
                                    
                                        $count = $term_taxonomy[0]['count']+1;
                                        $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$res[0]['term_taxonomy_id']]);
                                    }
                                }
                            
                        }
                             }
                          
                    }
                }
            }
            }
        }
    }
}
function tagmanage_addlink($content){
    global $wpdb;
    $Tag_manage_key = get_option('tag_manage_key');
    
    if($Tag_manage_key){
        
    }else{
        return $content;
    }
    $id = get_the_ID(); 
    $Tag_manage = get_option('Tag_manage_link');
   
        if(!empty($Tag_manage)){
            
            if((isset($Tag_manage['bqgl']) && is_array($Tag_manage['bqgl']) && in_array(2,$Tag_manage['bqgl'])  )||!isset($Tag_manage['bqgl'])){ 
            if(isset($Tag_manage['link']) && ($Tag_manage['link']==1)){
                $tags = $wpdb->get_results($wpdb->prepare('select a.* from ('.$wpdb->prefix . 'terms as a left join '.$wpdb->prefix . 'term_taxonomy as b on a.term_id=b.term_id) left join '.$wpdb->prefix . 'term_relationships as c on b.term_taxonomy_id =c.term_taxonomy_id  where b.taxonomy="post_tag" and c.object_id=%d',$id),ARRAY_A);
                if(!empty($tags)){
                    foreach ($tags as $val)
                    {
                        
                        $val['url'] =get_tag_link($val['term_id']);
                        if(isset($Tag_manage['hremove']) && $Tag_manage['hremove']==1){
                        if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches))
                        {
                            if(isset($Tag_manage['bold']) &&isset($Tag_manage['color']) && $Tag_manage['color']){
                                if($Tag_manage['bold']==1){
                                    
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'"><b style="color:'.$Tag_manage['color'].'">'.$val['name'].'</b></a>',$content,1);
                                    
                                }else{
                                    
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'" style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                                    
                                }
                                
                            }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color'])||(!$Tag_manage['color']))){
                                if($Tag_manage['bold']==1){
                                        
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'"><b>'.$val['name'].'</b></a>',$content,1);
                                    
                                }else{
                                    
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'">'.$val['name'].'</a>',$content,1);
                                    
                                }
                            }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'" style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                            }else{
                                $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'">'.$val['name'].'</a>',$content,1);
                                
                            }
                            
                        }
                        }else{
                                if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches))
                        {
                            if(isset($Tag_manage['bold']) &&isset($Tag_manage['color']) && $Tag_manage['color']){
                                if($Tag_manage['bold']==1){
                                    
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'"><b style="color:'.$Tag_manage['color'].'">'.$val['name'].'</b></a>',$content,1);
                                    
                                }else{
                                    
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'" style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                                    
                                }
                                
                            }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color'])||(!$Tag_manage['color']))){
                                if($Tag_manage['bold']==1){
                                        
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'"><b>'.$val['name'].'</b></a>',$content,1);
                                    
                                }else{
                                    
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'">'.$val['name'].'</a>',$content,1);
                                    
                                }
                            }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'" style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                            }else{
                                $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['url'].'">'.$val['name'].'</a>',$content,1);
                                
                            }
                            
                        }
                        }
                       
                    }
                }
            }else{
                $tags = $wpdb->get_results($wpdb->prepare('select a.* from ('.$wpdb->prefix . 'terms as a left join '.$wpdb->prefix . 'term_taxonomy as b on a.term_id=b.term_id) left join '.$wpdb->prefix . 'term_relationships as c on b.term_taxonomy_id =c.term_taxonomy_id  where b.taxonomy="post_tag" and c.object_id=%d',$id),ARRAY_A);
            
                
                if(!empty($tags))
                {
                    foreach ($tags as $val)
                    {
                         if(isset($Tag_manage['hremove']) && $Tag_manage['hremove']==1){
                                if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches))
                        { 
                            if(isset($Tag_manage['bold']) && isset($Tag_manage['color']) &&$Tag_manage['color']){
                                if($Tag_manage['bold']==1){
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<b style="color:'.$Tag_manage['color'].'">'.$val['name'].'</b>',$content,1);
                                }else{
                                    if($val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank" rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }elseif($val['tag_target'] && !$val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank">'.$val['name'].'</a>',$content,1);
                                    }elseif(!$val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'"  rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }else{
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                                    }
                                }
                                
                            }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color']) || !$Tag_manage['color'])){
                                if($Tag_manage['bold']==1){
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<b>'.$val['name'].'</b>',$content,1);
                                }
                            }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                    if($val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank" rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }elseif($val['tag_target'] && !$val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank" >'.$val['name'].'</a>',$content,1);
                                    }elseif(!$val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }else{
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                                    }
                            }
                            
                        }
                         }else{
                                if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches))
                        { 
                            if(isset($Tag_manage['bold']) && isset($Tag_manage['color']) &&$Tag_manage['color']){
                                if($Tag_manage['bold']==1){
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<b style="color:'.$Tag_manage['color'].'">'.$val['name'].'</b>',$content,1);
                                }else{
                                    if($val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank" rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }elseif($val['tag_target'] && !$val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank">'.$val['name'].'</a>',$content,1);
                                    }elseif(!$val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'"  rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }else{
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                                    }
                                }
                                
                            }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color']) || !$Tag_manage['color'])){
                                if($Tag_manage['bold']==1){
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<b>'.$val['name'].'</b>',$content,1);
                                }
                            }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                    if($val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank" rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }elseif($val['tag_target'] && !$val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" target="_blank" >'.$val['name'].'</a>',$content,1);
                                    }elseif(!$val['tag_target'] && $val['tag_nofollow']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'" rel="nofollow">'.$val['name'].'</a>',$content,1);
                                    }else{
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['name']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a  style="color:'.$Tag_manage['color'].'">'.$val['name'].'</a>',$content,1);
                                    }
                            }
                            
                        }
                         }
                     
                    }
        
                }
            }
            }
            //内链未处理
            if((isset($Tag_manage['bqgl']) && is_array($Tag_manage['bqgl']) && in_array(1,$Tag_manage['bqgl'])  )||!isset($Tag_manage['bqgl'])){ 
                    
                    //定义自增数量
                    $nladdnum = 0;
                    
                    //处理优先级
                     if(is_array($Tag_manage['bqgl']) && in_array(2,$Tag_manage['bqgl'])){
                            if(isset($tags) && !empty($tags)){
                                $sql ="SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where keywords not in(";
                                foreach($tags as $k=>$v){
                                    $sql.='"'.$v['name'].'",';
                                }
                                $sql = trim($sql,',');
                                $sql .=')   order by sort desc';
                                
                                $post1 = $wpdb->get_results($sql,ARRAY_A);
                            }else{
                                $post1 = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian   order by sort desc",ARRAY_A);
                            }
                        }else{
                            $post1 = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian  order by sort desc",ARRAY_A);
                        }
                    
                    if(!empty($post1)){
                        foreach($post1 as $key=>$val){
                            
                            if($val['target']==1){
                                $target ='target=_blank';
                            }else{
                                $target ='';
                            }
                            if($val['nofollow']==1){
                                $nofollow = 'rel="nofollow"';
                            }else{
                                $nofollow = '';
                            }
                             if(isset($Tag_manage['hremove']) && $Tag_manage['hremove']==1){
                                 if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches)){
                                $nladdnum++;
                                if(isset($Tag_manage['bold']) &&isset($Tag_manage['color']) && $Tag_manage['color']){
                                    
                                    if($Tag_manage['bold']==1){
                                        
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b style="color:'.$Tag_manage['color'].'">'.$val['keywords'].'</b></a>',$content,1);
                                        
                                    }else{
                                        
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                        
                                    }
                                    
                                }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color'])||(!$Tag_manage['color']))){
                                    if($Tag_manage['bold']==1){
                                            
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b>'.$val['keywords'].'</b></a>',$content,1);
                                        
                                    }else{
                                        
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                        
                                    }
                                }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                }else{
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                }
                                if($Tag_manage['nlnum']!=11 && $nladdnum>=$Tag_manage['nlnum'] ){
                                    break;
                                }
                            }
                             }else{
                            if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches)){
                                $nladdnum++;
                                if(isset($Tag_manage['bold']) &&isset($Tag_manage['color']) && $Tag_manage['color']){
                                    
                                    if($Tag_manage['bold']==1){
                                        
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b style="color:'.$Tag_manage['color'].'">'.$val['keywords'].'</b></a>',$content,1);
                                        
                                    }else{
                                        
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                        
                                    }
                                    
                                }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color'])||(!$Tag_manage['color']))){
                                    if($Tag_manage['bold']==1){
                                            
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b>'.$val['keywords'].'</b></a>',$content,1);
                                        
                                    }else{
                                        
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                        
                                    }
                                }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                }else{
                                    $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                }
                                if($Tag_manage['nlnum']!=11 && $nladdnum>=$Tag_manage['nlnum'] ){
                                    break;
                                }
                            }
                             }
                        }
                    }
                    if(isset($Tag_manage['nlnum']) && $nladdnum<$Tag_manage['nlnum'] || $Tag_manage['nlnum']==11){
                        if(isset($Tag_manage['pp']) && $Tag_manage['pp']==1){
                             if(is_array($Tag_manage['bqgl']) && in_array(2,$Tag_manage['bqgl'])){
                                if(isset($tags) && !empty($tags)){
                                    $sql ="SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where keywords not in(";
                                    foreach($tags as $k=>$v){
                                        $sql.='"'.$v['name'].'",';
                                    }
                                    $sql = trim($sql,',');
                                    $sql .=') and sort=0 order by LENGTH(keywords) desc';
                                    $post1 = $wpdb->get_results($sql,ARRAY_A);
                                }else{
                                     $post1 = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where sort=0  order by LENGTH(keywords) desc",ARRAY_A);
                                }
                            }else{
                                $post1 = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where sort=0  order by LENGTH(keywords) desc",ARRAY_A);
                            }
                            
                        }elseif(isset($Tag_manage['pp']) && $Tag_manage['pp']==2){
                             if(is_array($Tag_manage['bqgl']) && in_array(2,$Tag_manage['bqgl'])){
                                $sql ="SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where keywords not in(";
                                foreach($tags as $k=>$v){
                                    $sql.='"'.$v['name'].'",';
                                }
                                $sql = trim($sql,',');
                                $sql .=') and sort=0 order by LENGTH(keywords) asc';
                                $post1 = $wpdb->get_results($sql,ARRAY_A);
                            }else{
                                $post1 = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where sort=0  order by LENGTH(keywords) asc",ARRAY_A);
                            }
                            
                        }else{
                            if(is_array($Tag_manage['bqgl']) && in_array(2,$Tag_manage['bqgl'])){
                                if($tags){
                                    $sql ="SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where keywords not in(";
                                    foreach($tags as $k=>$v){
                                        $sql.='"'.$v['name'].'",';
                                    }
                                    $sql = trim($sql,',');
                                    $sql .=') and sort=0 order by id desc';
                                    $post1 = $wpdb->get_results($sql,ARRAY_A);
                                }else{
                                    $post1 = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where sort=0  order by id desc",ARRAY_A);
                                }
                            }else{
                                $post1 = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where sort=0  order by id desc",ARRAY_A);
                            }
                        }
                        
                        if(!empty($post1)){
                            foreach($post1 as $key=>$val){
                                if($val['target']==1){
                                    $target ='target=_blank';
                                }else{
                                    $target ='';
                                }
                                if($val['nofollow']==1){
                                    $nofollow = 'rel="nofollow"';
                                }else{
                                    $nofollow = '';
                                }
                                  if(isset($Tag_manage['hremove']) && $Tag_manage['hremove']==1){
                                       if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches)){
                                     $nladdnum++;
                                    if(isset($Tag_manage['bold']) &&isset($Tag_manage['color']) && $Tag_manage['color']){
                                        
                                        if($Tag_manage['bold']==1){
                                            
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b style="color:'.$Tag_manage['color'].'">'.$val['keywords'].'</b></a>',$content,1);
                                            
                                        }else{
                                            
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                            
                                        }
                                        
                                    }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color'])||(!$Tag_manage['color']))){
                                        if($Tag_manage['bold']==1){
                                                
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b>'.$val['keywords'].'</b></a>',$content,1);
                                            
                                        }else{
                                            
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                            
                                        }
                                    }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                    }else{
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                    }
                                }
                                  }else{
                                if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$content,$matches)){
                                     $nladdnum++;
                                    if(isset($Tag_manage['bold']) &&isset($Tag_manage['color']) && $Tag_manage['color']){
                                        
                                        if($Tag_manage['bold']==1){
                                            
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b style="color:'.$Tag_manage['color'].'">'.$val['keywords'].'</b></a>',$content,1);
                                            
                                        }else{
                                            
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                            
                                        }
                                        
                                    }elseif(isset($Tag_manage['bold']) && (!isset($Tag_manage['color'])||(!$Tag_manage['color']))){
                                        if($Tag_manage['bold']==1){
                                                
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'><b>'.$val['keywords'].'</b></a>',$content,1);
                                            
                                        }else{
                                            
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                            
                                        }
                                    }elseif(!isset($Tag_manage['bold']) && isset($Tag_manage['color']) && $Tag_manage['color']){
                                            $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg( $val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" style="color:'.$Tag_manage['color'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                    }else{
                                        $content=preg_replace('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($val['keywords']).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i','<a href="'.$val['link'].'" '.$target.' '.$nofollow.'>'.$val['keywords'].'</a>',$content,1);
                                    }
                                }
                                  }
                                if($Tag_manage['nlnum']!=11 && $nladdnum>=$Tag_manage['nlnum']){
                                    break;
                                }
                            }
                        }
                    }
                }
        }
         
        return $content;
}
function tagmanage_paymoney($root){
    $data = sanitize_url($_SERVER['SERVER_NAME']);
    $url = TAGMANAGE_URL.$root."?url={$data}&type=3&url1=".md5($data.TAGMANAGE_SALT);
    $defaults = array(
        'timeout' => 120,
        'connecttimeout'=>120,
        'redirection' => 3,
        'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        'sslverify' => FALSE,
    );
    $result = wp_remote_get($url,$defaults);
    $content = wp_remote_retrieve_body($result);
    $content = json_decode($content,true);
    
    if(!is_wp_error($result)){
        
        $content = wp_remote_retrieve_body($result);
        
        $content = json_decode($content,true);
        if($content['status']==1){
            
            return $content;
        }else{
            return tagmanage_paymoney1($root);
        }
    }else{
        return tagmanage_paymoney1($root);
    }

}
function tagmanage_paymoney1($root){
    $data =  tagmanage_url();
    
    $url = TAGMANAGE_URL.$root."?url={$data}&type=3&url1=".md5($data.TAGMANAGE_SALT);
    $defaults = array(
        'timeout' => 120,
        'connecttimeout'=>120,
        'redirection' => 3,
        'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        'sslverify' => FALSE,
    );
    $result = wp_remote_get($url,$defaults);
    $content = wp_remote_retrieve_body($result);
    setcookie('tagmanage_data_paymoney',$content,time()+3600*24);
    $content = json_decode($content,true);
    if(isset($content['status']) && $content['status']==1){
        
        return $content;
    }
}
function tagmanage_url(){
    $url1 = get_option('siteurl');
    $url1 = str_replace('https://','',$url1);
    $url1 = str_replace('http://','',$url1);
    $url1 = trim($url1,'/');
    $url1 = explode('/',$url1);
    return $url1[0];
}
function tagmanage_tagchange(){
    if(is_single()){
        function tagmanage_contents($content){
        
            // var_dump(tagmanage_addlink($content));
            return  tagmanage_addlink($content);
        }
        add_filter('the_content', 'tagmanage_contents',1);
    }
}
function tagmanage_preg($str)
{
    $str=strtolower(trim($str));
    $replace=array('\\','*','?','[','^',']','$','(',')','{','}','=','!','<','>','|',':','-',';','\'','\"','/','&','_','`');
    $str = str_replace($replace,'',$str);
    $str = str_replace('+','\+',$str);
     
    $str = str_replace('%','\%',$str);
    return $str;
}
function tagmanage_init(){
    if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
        global $wpdb;
        $data = [];
        $data['Tag_manage_key'] ='';
            $defaults = array(
                'timeout' => 120,
                'connecttimeout'=>120,
                'redirection' => 3,
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
                'sslverify' => FALSE,
            );
            $tagmanage_level = get_option('tagmanage_level');
            if(!isset($tagmanage_level[2]) || $tagmanage_level[2]<time()-24*3600){
                $url = 'https://www.rbzzz.com/api/money/level3?url='.tagmanage_url().'&type=3';
                $result = wp_remote_get($url,$defaults);
                if(!is_wp_error($result)){
                    $level = wp_remote_retrieve_body($result);
                    $level = json_decode($level,true);
                    $level1 = explode(',',$level['level']);
                    $level2 = $level1;
                    if(isset($level1[0]) && ($level1[0]==1 || $level1[0]==2)){
                        $level2[2] = time();
                        $level2[3] = $level['version'];
                        update_option('tagmanage_level',$level2);
                        $data['Tag_manage_key'] = get_option('tag_manage_key');
                    }
                }
            }else{
                $level1 = $tagmanage_level;
                $level = [];
                $level['version'] = $tagmanage_level[3];
                if(isset($level1[0]) && ($level1[0]==1 || $level1[0]==2)){
                    $data['Tag_manage_key'] = get_option('tag_manage_key');
                }
            }
            $level1[2] = $level['version'];
            $data['level'] = $level1;
            //保存数值预览
            $data['Tag_manage_link'] = get_option('Tag_manage_link');
            $url = 'https://www.rbzzz.com/api/kp/jifen?url='.tagmanage_url();
            $defaults = array(
                'timeout' => 4000,
                'connecttimeout'=>4000,
                'redirection' => 3,
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
                'sslverify' => FALSE,
            );
            $result = wp_remote_get($url,$defaults);
            if(!is_wp_error($result)){
                $jifen = wp_remote_retrieve_body($result);
                $jifen =$jifen?$jifen:0;
            }else{
                $jifen = 0;
            }
            $data['jifen'] = $jifen;
            $data['long'] = $wpdb->get_results('select * from  '.$wpdb->prefix.'baiduseo_long order by id desc',ARRAY_A);
            echo wp_json_encode(['code'=>1,'data'=>$data]);exit;
    }else{
        echo wp_json_encode(['code'=>0]);exit;
    }
}
    function tagmanage_add_pltag(){
        if(isset($_POST['nonce']) && isset($_POST['action']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']),'tagmanage')){
            $type = (int)$_POST['type'];
            $keywords = explode(',',$_POST['keyword']);
             $keywords =array_filter($keywords);
            $keywords = array_map('sanitize_text_field',$keywords);
            $tagmanage_tag_manage = get_option('Tag_manage_link');
            global $wpdb;
             $pay = tagmanage_paymoney('/api/index/pay_money');
            if($pay['msg']==1){
            if(!empty($keywords)){
                if($type==1){
                    foreach($keywords as $key=>$val){
                        if(isset($tagmanage_tag_manage['pp']) && $tagmanage_tag_manage['pp']==1){
                            $terms = $wpdb->get_results($wpdb->prepare('select a.* from '.$wpdb->prefix . 'terms as a left join '.$wpdb->prefix . 'term_taxonomy as b on a.term_id=b.term_id   where b.taxonomy="post_tag" and a.name=%s ORDER BY LENGTH(a.name) DESC',$val),ARRAY_A);
                        }elseif(isset($tagmanage_tag_manage['pp']) && $tagmanage_tag_manage['pp']==2){
                           $terms = $wpdb->get_results($wpdb->prepare('select a.* from '.$wpdb->prefix . 'terms as a left join '.$wpdb->prefix . 'term_taxonomy as b on a.term_id=b.term_id   where b.taxonomy="post_tag" and a.name=%s ORDER BY LENGTH(a.name) ASC',$val),ARRAY_A);
                        }else{
                           $terms = $wpdb->get_results($wpdb->prepare('select a.* from '.$wpdb->prefix . 'terms as a left join '.$wpdb->prefix . 'term_taxonomy as b on a.term_id=b.term_id   where b.taxonomy="post_tag" and a.name=%s',$val),ARRAY_A);
                        }
                        
                        if(!$terms){
                            $res = $wpdb->insert($wpdb->prefix."terms",['name'=>$val]);
                            $id = $wpdb->insert_id;
                            $wpdb->update($wpdb->prefix . 'terms',['slug'=>$id],['term_id'=>$id]);
                            $wpdb->insert($wpdb->prefix."term_taxonomy",['term_id'=>$id,'taxonomy'=>'post_tag']);
                            $id_1 = $wpdb->insert_id;
                            $baiduseo_tag_manage = get_option('Tag_manage_link');
                            if($baiduseo_tag_manage){
                                if(isset($baiduseo_tag_manage['auto']) && $baiduseo_tag_manage['auto']){
                                    $article = $wpdb->get_results('select * from '.$wpdb->prefix . 'posts where  post_status="publish" and post_type="post" order by ID desc limit 1000',ARRAY_A);
                                    if(!isset($baiduseo_tag_manage['num']) || !$baiduseo_tag_manage['num'] || $baiduseo_tag_manage['num']==11){
                                        
                                        foreach($article as $k=>$v){
                                           if(isset($baiduseo_tag_manage['hremove']) && $baiduseo_tag_manage['hremove']==1){
                                            if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($val).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                            {
                                                $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$id_1),ARRAY_A);
                                                $count = $term_taxonomy[0]['count']+1;
                                                $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                            }
                                           }else{
                                               if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($val).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                            {
                                                $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d' ,$id_1),ARRAY_A);
                                                $count = $term_taxonomy[0]['count']+1;
                                                $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                            }
                                           }
                                        }
                                    }else{
                                        foreach($article as $k=>$v){
                                            $shu = $wpdb->query($wpdb->prepare('select * from '.$wpdb->prefix .'term_relationships as a left join '.$wpdb->prefix .'term_taxonomy as b on a.term_taxonomy_id=b.term_taxonomy_id where b.taxonomy="post_tag" and a.object_id=%d' ,$v['ID']));
                                            if($shu>=$baiduseo_tag_manage['num']){
                                                break;
                                            }else{
                                                 if(isset($baiduseo_tag_manage['hremove']) && $baiduseo_tag_manage['hremove']==1){
                                                if(preg_match('{(?!((<.*?)|(<a.*?)|(<h[1-6].*?>)))('.tagmanage_preg($val).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                                {
                                                    $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                    $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);
                                                            
                                                    $count = $term_taxonomy[0]['count']+1;
                                                    $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                                }
                                                 }else{
                                                     if(preg_match('{(?!((<.*?)|(<a.*?)))('.tagmanage_preg($val).')(?!(([^<>]*?)>)|([^>]*?<\/a>))}i',$v['post_content'],$matches))
                                                {
                                                    $wpdb->insert($wpdb->prefix."term_relationships",['object_id'=>$v['ID'],'term_taxonomy_id'=>$id_1]);    
                                                    $term_taxonomy = $wpdb->get_results($wpdb->prepare('select * from '.$wpdb->prefix . 'term_taxonomy where  term_taxonomy_id=%d'  ,$id_1),ARRAY_A);
                                                            
                                                    $count = $term_taxonomy[0]['count']+1;
                                                    $res = $wpdb->update($wpdb->prefix . 'term_taxonomy',['count'=>$count],['term_taxonomy_id'=>$id_1]);
                                                }
                                                 }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }elseif($type==2){
                    foreach($keywords as $key=>$val){
                        $post1 = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix ."baiduseo_neilian where keywords =%s ",$val),ARRAY_A);
                        if(empty($post1)){
                         $wpdb->insert($wpdb->prefix."baiduseo_neilian",['keywords'=>$val,'link'=>'/',]);
                        }
                    }
                }
            
            }
            }else{
                 echo wp_json_encode(['msg'=>'请先授权','code'=>0]);
                exit;
            }
            echo wp_json_encode(['msg'=>'导入成功','code'=>1]);
            exit;
        }
         echo wp_json_encode(['msg'=>'导入失败','code'=>0]);
        exit;
        
}

?>