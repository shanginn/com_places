<?php
defined('_JEXEC') or die;

class PlacesHelper {
  
  static function getAllPlaces()
  {
    $places = new stdClass();

    $db    = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->select('
      p.id, p.town_id, p.type, p.lat, p.lng, p.address, p.phone,
      p.org_fax as fax, p.email, t.title as town');
    $query->leftJoin('#__places_town AS t ON p.town_id = t.id');
    $query->from('#__places_place AS p');
    $query->where('p.state = 1');
    $query->order('p.ordering');

    // Get the options.
    $db->setQuery($query);

    $places = $db->loadObjectList();

    return $places;
  }

  static function getAllTowns()
  {
    $towns = new stdClass();

    $db    = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->select('t.*');
    $query->from('#__places_town AS t');
    $query->where('t.state = 1');
    $query->order('t.ordering');

    // Get the options.
    $db->setQuery($query);

    $towns = $db->loadObjectList();

    return $towns;
  }

  static function getTownData()
  {
    $jinput = JFactory::getApplication()->input;
    
    $townData = json_decode($jinput->cookie->getJson('userData', 0), true);
    
    $town = false;
    
    if (isset($townData['town_id']) && $townData['town_id']){
      $db = JFactory::getDbo();
      $query  = $db->getQuery(true);
  
      $query->select('t.*');
      $query->from('#__places_town AS t');
      $query->where('t.state = 1 AND t.id ='.(int) $townData['town_id']);
  
      $db->setQuery($query);
  
      $town = $db->loadObject();
      
    }
    
    return $town;
  }
  
  public static function getTownsOptions()
  {
    $options = array();

    $db   = JFactory::getDbo();
    $query  = $db->getQuery(true);

    $query->select('id As value, title As text');
    $query->from('#__places_town AS t');
    $query->order('t.ordering');

    // Get the options.
    $db->setQuery($query);

    try
    {
      $options = $db->loadObjectList();
    }
    catch (RuntimeException $e)
    {
      JError::raiseWarning(500, $e->getMessage());
    }

    // Merge any additional options in the XML definition.
    //$options = array_merge(parent::getOptions(), $options);

    return $options;
  }
  // Определение ближайшего города по координатам
  // долг
  // шир
  // количество варинтов
  static function getNearestTown($lat, $lng, $count)
  {
    $db = JFactory::getDbo();
    $query  = $db->getQuery(true);

    $query->select('id, title, ( 6371 * ACOS( COS( RADIANS( '.$lat.' ) ) * COS( RADIANS( t.lat ) ) * COS( RADIANS( t.lng ) - RADIANS( '.$lng.' ) ) + SIN( RADIANS( '.$lat.' ) ) * SIN( RADIANS( t.lat ) ) ) ) AS distance');
    $query->from('#__places_town AS t');
    $query->where('t.state = 1');
    $query->order('distance');

    $db->setQuery($query, 0, $count);
    $towns = $db->loadObjectList();

    return $towns;
  }

  static function getNearestPlace($lat, $lng, $count)
  {
    if (!$lat && !$lng)
    {
      $app = JFactory::getApplication();
      $userData = json_decode($app->input->cookie->getJson('userData'));
      $lat = $userData->lng;
      $lng = $userData->lat;
    }
    $db = JFactory::getDbo();
    $distance = '( 6371 * ACOS( COS( RADIANS( '.$lat.' ) ) * COS( RADIANS( p.lat ) ) * COS( RADIANS( p.lng ) - RADIANS( '.$lng.' ) ) + SIN( RADIANS( '.$lat.' ) ) * SIN( RADIANS( p.lat ) ) ) )';
    $query  = $db->getQuery(true);
    
    $query->select('p.*,' . $distance . '  AS distance');
    $query->from('#__places_place AS p');
    $query->where('p.state = 1 AND p.type = 2 AND '.$distance.' < 210');
    $query->order('distance');
    
    $db->setQuery($query, 0, $count);
    $places = $db->loadObjectList();
    if(!empty($places)){
      $catalogue_ids = array();
      foreach($places as $place){
        $catalogue_ids[] = $place->catalogue_uid;
      }
      $query  = $db->getQuery(true);
      
      $query->select('u.username, u.email, u.phone');
      $query->from('#__catalogue_user AS u');
      $query->where('u.id IN ('.implode(',',$catalogue_ids).')');
      $db->setQuery($query);
  
      $places_cont = $db->loadObjectList();
      
      foreach($places as &$place){
        foreach($places_cont as $cont){
          if($place->email == $cont->email){
            $place->cont = $cont;
            break 2;
          }
        }
      }
    }
    return $places;
  }

  static function getTownByName($name)
  {
    $name = trim($name);
    $db = JFactory::getDbo();
    $query  = $db->getQuery(true);

    $query->select('t.*');
    $query->from('#__places_town AS t');
    $query->where('t.state = 1 AND t.title LIKE '. $db->Quote($name));

    $db->setQuery($query);

    $town = $db->loadObject();
    
    if (!empty($town) && $town->id)
    {
      $query  = $db->getQuery(true);
      $query->select('COUNT(p.id) as count');
      $query->from('#__places_town AS t');
      $query->join('LEFT', '#__places_place AS p ON p.town_id = t.id');
      $query->where('p.state = 1 AND p.type = 1 AND p.town_id = '.$town->id);
      $db->setQuery($query);
      
      $office_count = $db->loadResult();
      $town->office_count = (int)$office_count;
    
      $query  = $db->getQuery(true);
      $query->select('COUNT(p.id) as count');
      $query->from('#__places_town AS t');
      $query->join('LEFT', '#__places_place AS p ON p.town_id = t.id');
      $query->where('p.state = 1 AND p.type = 2 AND p.town_id = '.$town->id);
      $db->setQuery($query);
      
      $partner_count = $db->loadResult();
      $town->partner_count = (int)$partner_count;

    }
    
    return $town;
  }
  
  static function getTownById($id)
  {
    $db = JFactory::getDbo();
    
    $query  = $db->getQuery(true);
    $query->select('t.*');
    $query->from('#__places_town AS t');
    $query->where('t.state = 1 AND t.id = '.(int)$id);
    $db->setQuery($query);
    $town = $db->loadObject();
    
    if (!empty($town) && $town->id)
    {
      $query  = $db->getQuery(true);
      $query->select('COUNT(p.id) as count');
      $query->from('#__places_town AS t');
      $query->join('LEFT', '#__places_place AS p ON p.town_id = t.id');
      $query->where('p.state = 1 AND  p.type = 1 AND p.town_id = '.(int)$town->id);
      $db->setQuery($query);
      
      $office_count = $db->loadResult();
      $town->office_count = (int)$office_count;
    
      $query  = $db->getQuery(true);
      $query->select('COUNT(p.id) as count');
      $query->from('#__places_town AS t');
      $query->join('LEFT', '#__places_place AS p ON p.town_id = t.id');
      $query->where('p.state = 1 AND p.type = 2 AND p.town_id = '.(int)$town->id);
      $db->setQuery($query);
      
      $partner_count = $db->loadResult();
      $town->partner_count = (int)$partner_count;
    }
    
    return $town;
  }
  
  static function getPlaceById($id)
  {
    $db = JFactory::getDbo();
    
    $query  = $db->getQuery(true);
    $query->select('p.*');
    $query->from('#__places_place AS p');
    $query->where('p.state = 1 AND p.id = '.(int)$id);
    $db->setQuery($query);
    $place = $db->loadObject();
    
    $query  = $db->getQuery(true);
    $query->select('u.username, u.email, u.phone');
    $query->from('#__catalogue_user AS u');
    $query->where('u.id = '.$place->catalogue_uid);
    $db->setQuery($query);
    $place->cont = $db->loadObject();
    
    return $place;
  }

  static function getPlacesByIds($ids){
      $db = JFactory::getDbo();

      $query  = $db->getQuery(true);
      $query->select('p.*, u.username AS cont_username, u.discount, u.email AS cont_email, u.phone AS cont_phone');
      $query->from('#__places_place AS p');
      $query->rightJoin('#__catalogue_user AS u ON u.id = p.catalogue_uid');
      $query->where('p.state = 1 AND p.id in ('.implode(',',$ids).')');
      $db->setQuery($query);
      $places = array();
      foreach($db->loadObjectList() as $place){
          $places[$place->id] = $place;
      }
      return $places;
  }
  
  static function getPlaceBytown_id($id, $type = 1)
  {
    $db = JFactory::getDbo();
    
    $query  = $db->getQuery(true);
    $query->select('p.*');
    $query->from('#__places_place AS p');
    $query->where('p.state = 1 AND p.town_id = '.(int)$id.' AND p.type = '.$type);
    $db->setQuery($query);
    $place = $db->loadObject();
    return $place;
  }

  // Функция склонения
  public static function inflect($what, $inflection_id = 0) 
  {
    $inflected="";

    $parser = xml_parser_create();

    $data = @file_get_contents('http://export.yandex.ru/inflect.xml?name='.urlencode($what));
    if($data) {
     xml_parse_into_struct($parser,$data,$structure,$index);
     if($structure) {
      foreach($structure as $key){
        if(!isset($key['tag']) || !isset($key['value'])) { 
          continue;
        } elseif($key['tag'] == 'INFLECTION') {
            $inf[$what][$key['attributes']['CASE']]=$key['value'];
            if($key['attributes']['CASE']==$inflection_id){
               $inflected=$key['value'];
            }
          }
        }
      }
    }
    xml_parser_free($parser);
   if($inflected=="")
    $inflected=$what;

   return $inflected;
  }
}


