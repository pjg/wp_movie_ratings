// Ask the user before submiting the form
function delete_confirmation() {
	if (confirm("Are you sure you wish to delete this movie rating (including review)?\n\nBeware, that there is no undo.")) return true;
	else return false;
}
