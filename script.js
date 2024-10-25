document.addEventListener('DOMContentLoaded', () => {
    const emojiPicker = document.getElementById("emoji-picker");
    const emojiPopup = document.getElementById("emoji-popup");
    const selectedEmoji = document.getElementById("selected-emoji");

    // Function to toggle the emoji popup
    function toggleEmojiPopup() {
        emojiPopup.style.display = (emojiPopup.style.display === 'block') ? 'none' : 'block';
    }

    // Function to select an emoji and update the hidden input field
    function selectEmoji(emoji) {
        selectedEmoji.value = emoji; // Update hidden input with selected emoji
        emojiPicker.textContent = emoji; // Update the emoji picker with the selected emoji
        toggleEmojiPopup();  // Close the emoji popup after selection
        console.log("Emoji selected:", emoji); // Debugging to see the selected emoji
    }

    // Event listener to toggle the emoji popup on emoji picker click
    emojiPicker.addEventListener('click', toggleEmojiPopup);

    // Event listener to handle emoji selection
    emojiPopup.addEventListener('click', (event) => {
        if (event.target.tagName === 'SPAN') {
            selectEmoji(event.target.textContent); // Pass the emoji text to the selectEmoji function
        }
    });
        
    // Close emoji popup when clicking outside the picker and popup
    document.addEventListener('click', (event) => {
        if (!emojiPicker.contains(event.target) && !emojiPopup.contains(event.target)) {
            emojiPopup.style.display = 'none';
        }
    });
});

// Form validation function
function validateForm(event) {
    const selectedEmoji = document.getElementById('selected-emoji').value;
    const textEntry = document.querySelector('textarea[name="text_entry"]').value.trim();

    // If no emoji selected
    if (!selectedEmoji) {
        event.preventDefault();
        alert('Please select an emoji before submitting your status.');
        return false;
    }

    // If text is empty
    if (!textEntry) {
        event.preventDefault();
        alert('Please enter a status before submitting.');
        return false;
    }

    // If both emoji and text are provided, the form can be submitted
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    // Handle Refine Status Click
    document.querySelectorAll('.refine-icon').forEach(icon => {
        icon.addEventListener('click', async () => {
            const entryId = icon.getAttribute('data-id');
            const statusTextElement = icon.closest('.card-body').querySelector('.card-text span:last-child');
            const currentText = statusTextElement.textContent;

            // Confirm action
            if (!confirm('Are you sure you want to refine this status?')) return;

            try {
                const response = await axios.post('refine_status.php', {
                    id: entryId,
                    text_entry: currentText
                });

                if (response.data.success) {
                    statusTextElement.textContent = response.data.refined_text;
                    alert('Status refined successfully!');
                } else {
                    alert('Failed to refine status.');
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred while refining the status.');
            }
        });
    });

    // Handle Generate Image Click
    document.querySelectorAll('.generate-image-icon').forEach(icon => {
        icon.addEventListener('click', async () => {
            const entryId = icon.getAttribute('data-id');
            const statusTextElement = icon.closest('.card-body').querySelector('.card-text span:last-child');
            const currentText = statusTextElement.textContent;

            try {
                const response = await axios.post('generate_image.php', {
                    id: entryId,
                    text_entry: currentText
                });

                if (response.data.success) {
                    const imageUrl = response.data.image_url;
                    showModal(imageUrl);
                } else {
                    alert('Failed to generate image.');
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred while generating the image.');
            }
        });
    });

    // Function to Show Modal with Image
    function showModal(imageUrl) {
        // Create Modal HTML if it doesn't exist
        if (!document.getElementById('imageModal')) {
            const modalHTML = `
                <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="imageModalLabel">Generated Image</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body text-center">
                        <img src="${imageUrl}" alt="Generated Image" class="img-fluid">
                      </div>
                    </div>
                  </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        } else {
            document.querySelector('#imageModal .modal-body img').src = imageUrl;
        }

        // Show the Modal using Bootstrap's modal method
        $('#imageModal').modal('show');
    }
});
// Ensure Axios is loaded
// You can include Axios via CDN in your HTML head or before this script
// <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

// Function to refine status using OpenAI
// Function to refine status using OpenAI
async function refineStatus(entryId) {
    const textSpan = document.querySelector(`#entry-${entryId} .text-entry`);
    const currentText = textSpan.innerText;

    try {
        const response = await axios.post('refine_status.php', {
            entry_id: entryId,
            text_entry: currentText
        });

        if (response.data.success) {
            // Update the text in the DOM using textContent to avoid interpreting HTML
            textSpan.textContent = response.data.refined_text;
            // Re-initialize tooltips in case content changes
            $('[data-toggle="tooltip"]').tooltip();
        } else {
            alert('Failed to refine status: ' + response.data.message);
        }
    } catch (error) {
        console.error(error);
        alert('An error occurred while refining the status.');
    }
}

// Function to generate image using DALL-E
async function generateImage(entryId) {
    try {
        const response = await axios.post('generate_image.php', {
            entry_id: entryId
        });

        if (response.data.success) {
            const imageUrl = response.data.image_url;
            // Display the image in a modal
            showImageModal(imageUrl);
        } else {
            alert('Failed to generate image: ' + response.data.message);
        }
    } catch (error) {
        console.error(error);
        alert('An error occurred while generating the image.');
    }
}

// Function to share status (Placeholder)
function shareStatus(entryId) {
    // Implement sharing functionality as needed
    alert('Share functionality not implemented yet.');
}

// Function to show image in modal
function showImageModal(imageUrl) {
    // Create modal HTML if it doesn't exist
    if (!document.getElementById('imageModal')) {
        const modalHTML = `
            <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Generated Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body text-center">
                    <img src="${imageUrl}" alt="Generated Image" class="img-fluid">
                  </div>
                </div>
              </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    } else {
        // Update the image source
        document.querySelector('#imageModal .modal-body img').src = imageUrl;
    }

    // Show the modal
    $('#imageModal').modal('show');
}

