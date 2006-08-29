// Ask the user before submiting the form
function delete_confirmation() {
	if (confirm('You are about to delete this movie rating (including review).\n\nThis cannot be undone. Are you sure?')) return true;
	else return false;
}

// Toggle display of movie reviews in page mode
function toggle_review(id) {
	var review = document.getElementById(id)
	var img_array = review.parentNode.getElementsByTagName('img')
	var img = img_array[0]

	if (review && img) {
		// Show review
		if (review.style.display == 'none') {
			if (review.style.setProperty) { 
				review.style.setProperty('display', 'block', 'important')
			} else {
				review.style.display = 'block'
			}
			img.alt = 'Hide the review'
			img.width = 9
			img.height = 21
			img.src = img.src.replace(/plus/, 'minus')
		} else { // Hide review
			if (review.style.setProperty) {
				review.style.setProperty('display', 'none', 'important')
			} else {
				review.style.display = 'none'
			}
			img.alt = 'Show the review'
			img.width = 9
			img.height = 9
			img.src = img.src.replace(/minus/, 'plus')
		}
	}
}
