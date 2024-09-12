function supprimerPublication(event) {
	const button = event.target;
    const publication = button.closest(".feedy");
    publication.remove();
}

const buttons = document.getElementsByClassName("delete-feedy");
Array.from(buttons).forEach(function (button) {
    button.addEventListener("click", supprimerFeedy);
});