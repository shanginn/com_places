function init (places, mapid) {
  (function($){
    var customItemContentLayout = ymaps.templateLayoutFactory.createClass(
      // Флаг "raw" означает, что данные вставляют "как есть" без экранирования html.
      '<h2 class=ballon_header>{{ properties.balloonContentHeader|raw }}</h2>' +
      '<div class=ballon_body>{{ properties.balloonContentBody|raw }}</div>' +
      '<div class=ballon_footer>{{ properties.balloonContentFooter|raw }}</div>'
    );
    var myMap = new ymaps.Map(mapid, {
            center: [55.907394, 51.945404],
            zoom: 5,
            controls: ['smallMapDefaultSet'],
            flying: true
        }),

        clusterer = new ymaps.Clusterer({
            /**
             * Через кластеризатор можно указать только стили кластеров,
             * стили для меток нужно назначать каждой метке отдельно.
             * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/option.presetStorage.xml
             */
            preset: 'islands#invertedDarkorangeClusterIcons',
            groupByCoordinates: false,
            
            clusterDisableClickZoom: true,
            clusterHideIconOnBalloonOpen: false,
            
            clusterOpenBalloonOnClick: true,
            // Устанавливаем стандартный макет балуна кластера "Карусель".
            clusterBalloonContentLayout: 'cluster#balloonCarousel',
            // Устанавливаем собственный макет.
            clusterBalloonItemContentLayout: customItemContentLayout,
            // Устанавливаем режим открытия балуна. 
            // В данном примере балун никогда не будет открываться в режиме панели.
            clusterBalloonPanelMaxMapArea: 0,
            // Устанавливаем размеры макета контента балуна (в пикселях).
            clusterBalloonContentLayoutWidth: 250,
            clusterBalloonContentLayoutHeight: 120,
            // Устанавливаем максимальное количество элементов в нижней панели на одной странице
            clusterBalloonPagerSize: 5
            // Настройка внешего вида нижней панели.
            // Режим marker рекомендуется использовать с небольшим количеством элементов.
            // clusterBalloonPagerType: 'marker',
            // Можно отключить зацикливание списка при навигации при помощи боковых стрелок.
            // clusterBalloonCycling: false,
            // Можно отключить отображение меню навигации.
            // clusterBalloonPagerVisible: false
        }),
        /**
         * Функция возвращает объект, содержащий данные метки.
         * Поле данных clusterCaption будет отображено в списке геообъектов в балуне кластера.
         * Поле balloonContentBody - источник данных для контента балуна.
         * Оба поля поддерживают HTML-разметку.
         * Список полей данных, которые используют стандартные макеты содержимого иконки метки
         * и балуна геообъектов, можно посмотреть в документации.
         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/GeoObject.xml
         */
        getPointData = function (place) {
          var phone = place.phone.split(')'),
              fax = place.fax.split(')');
          return {
            //clusterCaption: place.address,
            balloonContentBody: (''+
              //'  <div class="place-map-block-">' +
              '    ' + (place.address ?
              '    <div class="place-info-text place-address">' +
              '      <span class="place-info-label">Адрес: </span>' +
              '      <span class="place-info-value">' + place.address + '</span>' +
              '    </div>' : '') +
              '    ' + (place.phone ?
              '    <div class="place-info-text place-phone">' +
              '      <span class="place-info-label">Телефон: </span>' +
              '      <span class="place-info-value">' +
              '        <a href="tel:' + place.phone + '">' +
              '          ' + place.phone +
              '        </a>' +
              '      </span>' +
              '    </div>' : '') +
              '    ' + (place.fax ?
              '    <div class="place-info-text place-fax">' +
              '      <span class="place-info-label">Факс: </span>' +
              '      <span class="place-info-value">' + place.fax + '</span>' +
              '    </div>' : '') +
              '    ' + (place.email ?
              '    <div class="place-info-text place-email">' +
              '      <span class="place-info-label">Email: </span>' +
              '      <span class="place-info-value">' +
              '        <a href="mailto:' + place.email + '">' +
              '          ' + place.email +
              '        </a>' +
              '      </span>' +
              '    </div>' : '') +
              //'  </div>'+
              ''),
            hintContent: place.address
          };
        },
        /**
         * Функция возвращает объект, содержащий опции метки.
         * Все опции, которые поддерживают геообъекты, можно посмотреть в документации.
         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/GeoObject.xml
         */
        getPointOptions = function () {
          return {
            preset: 'islands#darkorangeIcon'
          };
        },
        points = [],
        geoObjects = [],
        geoIds = [],
        placesCollection = new ymaps.GeoObjectCollection({}, {geodesic: true}),
        $map = $(mapid);

    var i = 0;
    $.each(places, function(index, place) {
      geoObjects[index] = new ymaps.Placemark([place.lat, place.lng], getPointData(place), getPointOptions());
      geoIds[place.id] = index;
    });
    
    clusterer.options.set({
      gridSize: 80,
      clusterDisableClickZoom: true,
      clusterballoonContentBodyLayout: "cluster#balloonCarouselContent",
    });

    clusterer.add(geoObjects);
    myMap.geoObjects.add(clusterer);

    myMap.setBounds(clusterer.getBounds(), {
      checkZoomRange: true
    });

    $('.place-look-wrap').click(function(e) {
      var placeId = $(this).data('placeId'),
          point   = geoObjects[geoIds[placeId]];

      myMap.setCenter(point.geometry.getCoordinates(), 14);
      point.balloon.open();
    });

  })(jQuery);
};