// Ask the user before submiting the form
function delete_confirmation() {
	if (confirm("You are about to delete this movie rating (including review).\n\nThis cannot be undone. Are you sure?")) return true;
	else return false;
}

// Toggle display of movie reviews in page mode
function toggle_review(id) {
	var review = document.getElementById(id)
	var img_array = review.parentNode.getElementsByTagName("img")
	var img = img_array[0]

	if (review && img) {
		// Show review
		if (review.style.display == "none") {
			review.style.display = "block"
			img.alt = "Hide the review"
			img.src = img.src.replace(/plus/, 'minus')
		} else { // Hide review
			review.style.display = "none"
			img.alt = "Show the review"
			img.src = img.src.replace(/minus/, 'plus')
		}
	}
}
