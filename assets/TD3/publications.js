function supprimerFeedy(event) {
	let button = event.target;
    let feedy = button.closest(".feedy");
    feedy.remove();
}

let buttons = document.getElementsByClassName("delete-feedy");
Array.from(buttons).forEach(function (button) {
    button.addEventListener("click", supprimerFeedy);
});