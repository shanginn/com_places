jQuery(function($){
  ymaps.ready(function(){
    myMap = firstInit();
    
    function findByAddress() {
      var address = $('#jform_title').val();

      find(myMap, address, 'locality', function(geoObj){
        var coords = geoObj.geometry.getCoordinates();
        if(coords) {
          setPoint(myMap, coords);
          $('#jform_lat').val(coords[0]);
          $('#jform_lng').val(coords[1]);
        }
      });
    }

    $('#addressSearchBtn').click(findByAddress);
    $('#coordsSearchBtn').click(findByCoords);
  });
});