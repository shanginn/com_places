//TODO: remove global functions;
(function($){
  firstInit = function() {
    var lat = $('#jform_lat').val(),
        lng = $('#jform_lng').val()
    
    var map = new ymaps.Map("ymap", {
      center: [ lat || '55.160283', lng || '61.400856'],
      zoom: 11
    });

    if(lat && lng)
      setPoint(map, [lat, lng]);
    return map;
  }
  findByCoords = function() {
    var lat = $('#jform_lat').val(),
        lng = $('#jform_lng').val();
    
    search_query = [lat, lng];
    find(myMap, search_query, 'locality', function(geoObj){
      var coords = geoObj.geometry.getCoordinates();
      setPoint(myMap, coords);
    });
  }

  find = function(map, location, kind, callback) {
    // Kind
    // house    - дом;
    // street   - улица;
    // metro    - станция метро;
    // district - район города;
    // locality - населенный пункт (город/поселок/деревня/село/...).
    if(!location) return;
    if(!kind) kind = 'house';
    ymaps
      .geocode(location, {results: 1, kind: kind})
      .then(function(res){
        callback(res.geoObjects.get(0));
      });
  }

  setPoint = function(map, coords) {
    map.geoObjects.removeAll();
    map.geoObjects.add(new ymaps.Placemark(coords));
    map.setCenter(coords);

    return coords;
  }
})(jQuery);