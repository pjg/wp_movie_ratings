
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
			var message = $('message')
			if ($F('url').match(/^http:\/\/(us\.|uk\.|akas\.){0,1}imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i)) {
				Effect.Fade('message', {duration: 0.4, queue: 'end'})
				var pars = 'rating=' + this.id.substr(6) + '&url=' + escape($F('url'))
				var myAjax = new Ajax.Request('../../../wp-admin/edit.php?page=wp_movie_ratings.php', { method: 'post', parameters: pars, onComplete: show_response })
			} else {
				message.setAttribute('class', 'error')
				message.innerHTML = '<p><strong>Error: wrong imdb link.</strong></p>'
				Effect.Appear('message', {duration: 0.4, queue: 'end'})
				$('url').focus()
			}
		}, false)
	})
}

// show AJAX response
function show_response(originalRequest) {
	var message = $('message')
	var response = unescape(originalRequest.responseText)
 	var matches = response.match(/<div id="message" class="(.+?)">(.*?)<\/div>/)

	if (matches.length == 3) {
		message.setAttribute('class', matches[1])
		message.innerHTML = matches[2]
	}
	else {
		message.setAttribute('class', 'error')
		message.innerHTML = '<p><strong>Unrecognized AJAX response.</strong></p>'
	}

	Effect.Appear('message', {duration: 0.4, queue: 'end'})
}

addEvent(window, 'load', add_behaviour);
