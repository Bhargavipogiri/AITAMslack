(() => {
  const form = document.getElementById('askQuestionForm');
  if (!form) return;

  const title = document.getElementById('questionTitle');
  const body = document.getElementById('questionBody');
  const category = document.getElementById('questionCategory');
  const titleCounter = document.getElementById('titleCounter');
  const bodyCounter = document.getElementById('bodyCounter');
  const submit = document.getElementById('submitQuestionButton');

  const count = (field, output, max) => {
    output.textContent = `${field.value.length.toLocaleString()}/${max.toLocaleString()}`;
  };

  title.addEventListener('input', () => count(title, titleCounter, 220));
  body.addEventListener('input', () => count(body, bodyCounter, 10000));

  form.addEventListener('submit', (event) => {
    if (title.value.trim().length < 15 || title.value.trim().length > 220) {
      event.preventDefault();
      title.setCustomValidity('Enter a title between 15 and 220 characters.');
      title.reportValidity();
      title.setCustomValidity('');
      title.focus();
      return;
    }
    if (!category.value) {
      event.preventDefault();
      category.setCustomValidity('Please choose a category.');
      category.reportValidity();
      category.setCustomValidity('');
      category.focus();
      return;
    }
    if (body.value.trim().length < 30 || body.value.trim().length > 10000) {
      event.preventDefault();
      body.setCustomValidity('Enter details between 30 and 10,000 characters.');
      body.reportValidity();
      body.setCustomValidity('');
      body.focus();
      return;
    }
    submit.disabled = true;
    submit.querySelector('span:first-child').textContent = 'Checking question…';
  });

  count(title, titleCounter, 220);
  count(body, bodyCounter, 10000);
})();
