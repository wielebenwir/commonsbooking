jQuery(document).ready(function ($) {

  var $adresses = $('.cb-address');

  $adresses.each(function() {
    var $map_button = $(this).find('a.cb-button');
    if($map_button) {
      $timeframe = $map_button.closest('.cb-timeframe');
      timeframe_id = $timeframe.attr('id');

      var coords;

      if(cb_map_timeframes_geo[timeframe_id]) {
        coords = cb_map_timeframes_geo[timeframe_id];
      }

      if(coords && coords.lat && coords.lon) {
        var href = 'https://www.openstreetmap.org/?mlat='+coords.lat+'&mlon='+coords.lon+'#map=19/'+coords.lat+'/'+coords.lon+'&layers=C';
        $map_button.attr('href', href);
      }
      else {
        var href = $map_button.attr('href');
        href = href.replace('http://maps.google.com/?q', 'https://www.openstreetmap.org/search?query');
        $map_button.attr('href', href);
      }
    }
  });
});
