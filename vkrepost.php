<?
/*Класс получает количество репостов по каждому участнику
 * @param $owner_id = индификатор приложения в vk
 * @param $page = url страницы которую репостят
 * @param $count = кол-во забираймых новостей за один запрос макс 100
 * @param $maxNews = общее кол-во выбираймых новостей у пользователя в vk
 * Вызов vkrepost::update_repost($owner_id, $page, $maxNews,$count);
 */
class vkrepost{
    private $_userList;
    private $_repostUser = array();
    protected static $_countUser = 0;
    protected $_owner_id;
    protected $_maxNews;
    protected $_count;
    protected $_page;
            
    private function __construct($owner_id, $page, $maxNews,$count) {
        $this->_owner_id = $owner_id;
        $this->_page = $page;
        $this->_maxNews = intval($maxNews);
        if (intval($count)>100){
            $count = 100;
        }
        $this->_count = $count;
    }

    private function get_vk_likes() {              
        $curl = curl_init(); 
        $url = "https://api.vk.com/method/likes.getList?type=sitepage&owner_id=".$this->_owner_id."&page_url=".$this->_page;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_results = curl_exec ($curl);
        curl_close($curl);    
        $res = json_decode($curl_results,true);
        $this->_userList = $res["response"]["users"];

        }

        
    private function get_user_info($uids) {  
        $fields = 'uid,first_name,last_name,nickname,screen_name,sex,city,country,timezone,photo,photo_medium,photo_big,has_mobile,rate,online,counters';
        $curl = curl_init(); 
        $url = "https://api.vk.com/method/users.get?&uids=".$uids."&fields=".$fields."&name_case=nom";
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept-Language: ru,en-us'));
        $curl_results = curl_exec ($curl);
        curl_close($curl);   
        $res = json_decode($curl_results,true);
        return $res["response"][0] ;
        }
        
     private function get(){
       $this->get_vk_likes();
       if($this->_userList){
            foreach ($this->_userList as $users){
                $this->getUsersPosts($users);
       }    
            }
        return $this->_repostUser;//вернет количество репостов каждого участника, и их список
        
    }
    
     private function getUsersPosts ($owner_id, $offset = 0) {
        $i = vkrepost::$_countUser;   
        //Если обыскали $maxNews новостей и не нашли
        if ($offset > $this->_maxNews - $this->_count) {
            return false;
        }     
        //Формируем URL
        $curl = curl_init(); 
        $url = 'https://api.vk.com/method/wall.get?';
        $url .= 'owner_id='.$owner_id.'&';
        $url .= 'offset='.$offset.'&';
        $url .= 'count='.$this->_count.'&';
        $url .= 'filter=owner';          
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_results = curl_exec ($curl);
        curl_close($curl);    
        $data = json_decode($curl_results,true);          
        //Если вдруг страница пользователя "заморожена" или удалена 
        if (!isset($data['response'])) {
            return false;
        }
            
        $response = $data['response'];          
        //Обрабатываем $count новостей
        foreach ($response as $news) {         
            if (($news["attachment"]["link"]["url"])!= $this->_page."/") continue;                                                         
                 $users[$i] = array_merge($this->get_user_info($owner_id),$news);
                 $this->_repostUser = array_merge($this->_repostUser,$users); 
                  vkrepost::$_countUser++;
        }               
        $offset += $this->_count; //Увеличиваем смещение       
        $this->getUsersPosts($owner_id, $offset); //Рекурсия
        
    }
    static function update_repost($vkId, $page, $maxNews, $count){    
        $obj = new vkrepost($vkId, $page, $maxNews, $count);
        return $obj->get(); 
       
      }

}
 ?>
