/* Universal Admin Logic 
   - Handles Delete Modal
   - Handles Delete Actions based on URL
*/

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. DELETE TOGGLE LOGIC (Event Delegation) ---
    document.addEventListener('click', function(e) {
        // Check for delete button click
        const deleteBtn = e.target.closest('.toggle-delete-btn');
        
        if (deleteBtn) {
            e.preventDefault(); 
            
            // Get ID and Name from button
            const idToDelete = deleteBtn.dataset.movieId;
            const nameToDelete = deleteBtn.dataset.name; // Get the name
            
            // Pass ID to the Confirm button
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            const nameSpan = document.getElementById('deleteItemName'); // Get the text span
            
            if(confirmBtn) {
                confirmBtn.dataset.deleteId = idToDelete;
                
                // Inject the name into the modal text
                if(nameSpan && nameToDelete) {
                    nameSpan.textContent = `"${nameToDelete}"`;
                    nameSpan.style.color = "#dc2626"; // Optional: make it red
                } else if (nameSpan) {
                    nameSpan.textContent = "this item"; // Fallback
                }
                
                // Show the modal
                const modal = document.getElementById('contdelete');
                if(modal) modal.classList.add('show');
            }
        }
    });

    // --- 2. CONFIRM DELETE LOGIC ---
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const id = this.dataset.deleteId;
            let deleteUrl = '';
            
            // Get current page name from URL to decide which ID to send
            const path = window.location.pathname;

            if (path.includes('movies')) {
                deleteUrl = `asserts/src/delete_script.php?mid=${id}`;
            } 
            else if (path.includes('series')) {
                deleteUrl = `asserts/src/delete_script.php?sid=${id}`;
            } 
            else if (path.includes('episodes')) {
                deleteUrl = `asserts/src/delete_script.php?eid=${id}`;
            } 
            else if (path.includes('comment')) {
                deleteUrl = `asserts/src/delete_script.php?cid=${id}`;
            }

            if (deleteUrl) {
                window.location.href = deleteUrl;
            } else {
                console.error("Error: Could not detect page type for deletion.");
            }
        });
    }

    // --- 3. CANCEL / CLOSE MODAL LOGIC ---
    const cancelBtn = document.querySelector('.container-delete .two');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = document.getElementById('contdelete');
            if(modal) modal.classList.remove('show');
        });
    }
    
    // --- 4. TOGGLE SIDEBAR (Mobile) ---
    const toggleBtn = document.getElementById('toggleSidebar');
    const closeSidebarBtn = document.getElementById('toggleSidebarclose');
    const sidebar = document.getElementById('sidebar');

    if(toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
    }
    
    if(closeSidebarBtn && sidebar) {
        closeSidebarBtn.addEventListener('click', () => sidebar.classList.remove('show'));
    }

    // --- 5. USER DROPDOWN ---
    const userMenu = document.getElementById('userMenu');
    const dropdown = document.getElementById('dropdownMenu');
    
    if(userMenu && dropdown) {
        userMenu.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent immediate closing
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }
});