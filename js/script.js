const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

if (signUpButton && container) {
	signUpButton.addEventListener('click', () => {
		container.classList.add('right-panel-active');
	});
}

if (signInButton && container) {
	signInButton.addEventListener('click', () => {
		container.classList.remove('right-panel-active');
	});
}

function showPopup(message, type = 'info') {
	// remove existing popup
	const existing = document.getElementById('ezpopup');
	if (existing) existing.remove();

	const popup = document.createElement('div');
	popup.id = 'ezpopup';
	popup.textContent = message;
	popup.style.position = 'fixed';
	popup.style.right = '20px';
	popup.style.top = '20px';
	popup.style.zIndex = 9999;
	popup.style.padding = '12px 18px';
	popup.style.borderRadius = '6px';
	popup.style.color = '#fff';
	popup.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
	popup.style.maxWidth = '320px';
	popup.style.wordWrap = 'break-word';

	if (type === 'success') popup.style.background = '#4caf50';
	else if (type === 'error') popup.style.background = '#f44336';
	else popup.style.background = '#333';

	document.body.appendChild(popup);

	// auto-hide
	setTimeout(() => {
		popup.style.transition = 'opacity 0.4s ease';
		popup.style.opacity = '0';
		setTimeout(() => popup.remove(), 450);
	}, 4000);
}
