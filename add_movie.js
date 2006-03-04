
//var url = 'http://wieprz.oi.pg.gda.pl/pawel/wp_movie_ratings/index.php'

// AJAX activity indicators
Ajax.Responders.register({
  onCreate: function() {
    if($('loading') && Ajax.activeRequestCount > 0)
      Effect.Appear('loading', {duration: 0.4, queue: 'end'})
  },
  onComplete: function() {
    if($('loading') && Ajax.activeRequestCount == 0)
      Effect.Fade('loading', {duration: 0.4, queue: 'end'})
  }
})

// Cross-browser AddEvent function
function addEvent(obj, evType, fn) { 
	if (obj.addEventListener) {
		obj.addEventListener(evType, fn, false); 
		return true; 
	} else if (obj.attachEvent) { 
		var r = obj.attachEvent("on"+evType, fn); 
		return r; 
	} else { 
		return false; 
	} 
}

// attach separate AJAX call to each star
function add_behaviour() {
	// get <a href> stars
	var elements = $A( $('rating').getElementsByTagName('a') )
	
	elements.each( function(node) {
		node.addEventListener('click', function () {
			var msg = $('message')
			if ($F('url').match(/^http:\/\/(us\.|uk\.|akas\.){0,1}imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i)) {
				msg.style.display = 'none'
				var pars = 'rating=' + this.id.substr(6) + '&url=' + escape($F('url'))
				var myAjax = new Ajax.Request('../../../wp-admin/edit.php?page=wp_movie_ratings.php', { method: 'post', parameters: pars, onComplete: show_response })
			} else {
				msg.innerHTML = '<p class="error">Error: wrong imdb link.</p>'
				msg.style.display = 'block'
			}
		}, false)
	})
}

// show AJAX response
function show_response(originalRequest) {
	var msg = $('message')
	msg.innerHTML = unescape(originalRequest.responseText)
	msg.style.display = 'block'
}

addEvent(window, 'load', add_behaviour);
