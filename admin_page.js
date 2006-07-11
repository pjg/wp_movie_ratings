// Ask the user before submiting the form
function delete_confirmation() {
	if (confirm("You are about to delete this movie rating (including review).\n\nThis cannot be undone. Are you sure?")) return true;
	else return false;
}
