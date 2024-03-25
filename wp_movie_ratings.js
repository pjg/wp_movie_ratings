// Ask the user before submiting the form
function delete_confirmation() {
  if (confirm('You are about to delete this movie rating (including review).\n\nThis cannot be undone. Are you sure?')) return true;
  else return false;
}

// Toggle display of movie reviews in page mode
function toggle_review(id) {
  var review = document.getElementById(id)
  var expand = review.parentNode.querySelector('.expand')
  var collapse = review.parentNode.querySelector('.collapse')

  if (review && expand && collapse) {
    // Show the review
    if (review.style.display == 'none') {
      if (review.style.setProperty) {
        review.style.setProperty('display', 'block', 'important')
      } else {
        review.style.display = 'block'
      }

      expand.style.display = 'none'
      collapse.style.display = 'block'
    } else { // Hide the review
      if (review.style.setProperty) {
        review.style.setProperty('display', 'none', 'important')
      } else {
        review.style.display = 'none'
      }

      collapse.style.display = 'none'
      expand.style.display = 'block'
    }
  }
}
