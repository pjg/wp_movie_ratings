// Ask the user before submiting the form
function delete_confirmation() {
	if (confirm("You are about to delete this movie rating (including review).\n\nThis cannot be undone. Are you sure?")) return true;
	else return false;
}

// Toggle display of movie reviews in page mode
function toggle_review(id) {
	r = document.getElementById(id)
	if (r) {
		// Show review
		if (r.style.display == "none") r.style.display = "block"
			else r.style.display = "none"
		}

		// Change image & alt description
		r.parentNode.childNodes[1].childNodes[0].firstChild.alt = "Hide the review"

		// Change title on anchor

}
