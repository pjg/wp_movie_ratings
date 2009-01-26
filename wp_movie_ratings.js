// Ask the user before submiting the form
function delete_confirmation() {
  if (confirm('You are about to delete this movie rating (including review).\n\nThis cannot be undone. Are you sure?')) return true;
  else return false;
}

// Toggle display of movie reviews in page mode
function toggle_review(id) {
  var review = document.getElementById(id)
  var img_array = review.parentNode.getElementsByTagName('img')
  var img_plus = img_array[0]
  var img_minus = img_array[1]

  if (review && img_plus && img_minus) {
    // Show the review
    if (review.style.display == 'none') {
      if (review.style.setProperty) {
        review.style.setProperty('display', 'block', 'important')
      } else {
        review.style.display = 'block'
      }

      img_plus.style.display = 'none'
        img_minus.style.display = 'block'

    } else { // Hide the review
      if (review.style.setProperty) {
        review.style.setProperty('display', 'none', 'important')
      } else {
        review.style.display = 'none'
      }

      img_minus.style.display = 'none'
        img_plus.style.display = 'block'
    }
  }
}
