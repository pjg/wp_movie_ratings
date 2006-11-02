
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
		obj.addEventListener(evType, fn, false)
		return true
	} else if (obj.attachEvent) {
		var r = obj.attachEvent('on' + evType, fn)
		return r
	} else {
		return false
	}
}

// escape() that works well with ALL Unicode characters
// http://www.kanolife.com/escape/2006/03/escape-and-unescape-javascript.html
function unicode_escape(pstrString) {
	if (pstrString == '') {
		return ''
	}
	var iPos = 0
	var strOut = ''
	var strChar
	var strString = escape(pstrString)
	while (iPos < strString.length) {
		strChar = strString.substr(iPos, 1)
		if (strChar == '%') {
			strNextChar = strString.substr(iPos + 1, 1)
			if (strNextChar == 'u') {
				strOut += strString.substr(iPos, 6)
				iPos += 6
			} else {
				strOut += '%u00' + strString.substr(iPos + 1, 2)
				iPos += 3
			}
		} else {
			strOut += strChar
			iPos++
		}
	}

	// encode HTML entities (< > " ' &) plus space, equal sign and cross (  = #) separately so you can write HTML code in your review and it sort of stays html
	return strOut.replace(/%u0020/g, "%20").replace(/%u003D/g, "%3D").replace(/%u003C/g, "%3C").replace(/%u003E/g, "%3E").replace(/%u0022/g, "%22").replace(/%u0027/g, "%27").replace(/%u0026/g, "%26").replace(/%u0023/g, "%23")
}

// Clear query parameters in imdb links (added by imdb.com while searching for titles there)
function beautify_imdb_uri(url) {
	var i = url.indexOf('?')
	if (i > 0) {
		return url.substring(0, i)
	} else {
		return url
	}
}

// Attach separate AJAX call to each star
function add_behaviour() {
	// get <a href> stars
	var elements = $A( $('rating').getElementsByTagName('a') )

	elements.each( function(node) {
		node.addEventListener('click', function () {
			var message = $('message')
			if ($F('url').match(/^http:\/\/.*imdb\.com\/title\/tt([0-9]{7})(\/){0,1}.*$/i)) {
				var rating = parseInt(this.id.substr(6))
				// make selected rating 'stuck'
				$A( $('rating').getElementsByTagName('a') ).each( function(el) {
					var id = parseInt(el.id.substr(6))
					if (rating >= id) el.style.background="url(star_rating.gif) bottom left"
					else el.style.backgroundPosition = "top left"
				})
				// execute AJAX call
				Effect.Fade('message', {duration: 0.4, queue: 'end'})
				var pars = 'action=add&rating=' + rating + '&url=' + escape(beautify_imdb_uri($F('url'))) + '&review=' + unicode_escape($F('review'))
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

// Focus window and appropriate input field
function initial_focus() {
	// focus window
	if (window.focus) window.focus()

	// focus input -> review, if we have imdb link already or url in another case
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
	if (url.match(/^http:\/\/.*imdb\.com\/title\/tt([0-9]{7})(\/){0,1}.*$/i)) {
		$('url').value = beautify_imdb_uri(url)
	}
}

// Show AJAX response
function show_response(originalRequest) {
	var message = $('message')
	var response = unescape(originalRequest.responseText)
 	var matches = response.match(/<div id="message" class="(.+?)">(.*?)<\/div>/i)
	
	// Not logged in
	if (!matches) {
		message.setAttribute('class', 'error')
		message.innerHTML = '<p><strong>Error: movie rating not added. Perhaps you are not logged in?</strong></p>'
	} else {
		// Valid response
		if (matches.length == 3) {
			message.setAttribute('class', matches[1])
			message.innerHTML = matches[2]
		} else {
			// Unknown error
			message.setAttribute('class', 'error')
			message.innerHTML = '<p><strong>Error: unrecognized AJAX response.</strong></p>'
		}
	}

	// Show message
	Effect.Appear('message', {duration: 0.4, queue: 'end'})
	
	// Close the pop-up window on successful update
	if (matches && (matches[1] == 'updated fade')) {
		// Close the window (after a little over 1s delay, so you can actually read the message)
		setTimeout("close_window()", 1200);
	}
}

// Empty effects queue and close the window
function close_window() {
	// We clear the effects queue because the browser crashes if you issue a window.close when effects are still running
	Effect.Queues.get('global').each(function (e) { e.cancel(); })
	window.close()
}

// Add onLoad events
addEvent(window, 'load', add_behaviour)
addEvent(window, 'load', parse_uri)
addEvent(window, 'load', initial_focus)
