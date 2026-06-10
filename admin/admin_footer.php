<footer style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border-color); color: #9ca3af; font-size: 0.8rem; text-align: center;">
        &copy; <?php echo date('Y'); ?> Music Equipment and Instrument Store | Admin Panel
    </footer>
</div> 

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar if user clicks on the overlay
document.getElementById('sidebarOverlay').addEventListener('click', function() {
    toggleSidebar();
});
</script>

</body>
</html>