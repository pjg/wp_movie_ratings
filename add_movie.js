
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

// Attach separate AJAX call to each star
function add_behaviour() {
	// get <a href> stars
	var elements = $A( $('rating').getElementsByTagName('a') )

	elements.each( function(node) {
		node.addEventListener('click', function () {
			var message = $('message')
			if ($F('url').match(/^http:\/\/.*imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i)) {
				Effect.Fade('message', {duration: 0.4, queue: 'end'})
				var pars = 'rating=' + this.id.substr(6) + '&url=' + escape($F('url')) + '&review=' + $F('review')
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

function initial_focus() {
	// focus window
	if (window.focus) window.focus()

	// focus input -> review, if we have imdb link already, or url in another case
	var url = $('url')
	var review = $('review')

	if (url.value.match(/imdb\.com/)) review.focus()
	else url.focus()
}

// Parse url and paste it into <input type="text" name="url"> if it's imdb.com page
function parse_uri() {
	// No url, nothing to parse
	if (location.href.indexOf('?url=') == -1) return

	// Parse and set as url if it is imdb movie page
	var url = unescape(location.href.substring(location.href.indexOf('?url=') + 5))
	if (url.match(/^http:\/\/.*imdb\.com\/title\/tt([0-9]{7})(\/){0,1}$/i)) {
		$('url').value = url
	}
}

// Show AJAX response
function show_response(originalRequest) {
	var message = $('message')
	var response = unescape(originalRequest.responseText)
 	var matches = response.match(/<div id="message" class="(.+?)">(.*?)<\/div>/)

	// Valid response
	if (matches.length == 3) {
		message.setAttribute('class', matches[1])
		message.innerHTML = matches[2]

		// Close the pop-up window on successful update
		if (matches[1] == 'updated fade') window.close()
	}
	// Unknown error
	else {
		message.setAttribute('class', 'error')
		message.innerHTML = '<p><strong>Unrecognized AJAX response.</strong></p>'
	}

	Effect.Appear('message', {duration: 0.4, queue: 'end'})
}

// Add onLoad events
addEvent(window, 'load', add_behaviour)
addEvent(window, 'load', parse_uri)
addEvent(window, 'load', initial_focus)
