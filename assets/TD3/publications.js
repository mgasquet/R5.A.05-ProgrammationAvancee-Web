function supprimerFeedy(event) {
	const button = event.target;
    const feedy = button.closest(".feedy");
    feedy.remove();
}

const buttons = document.getElementsByClassName("delete-feedy");
Array.from(buttons).forEach(function (button) {
    button.addEventListener("click", supprimerFeedy);
});