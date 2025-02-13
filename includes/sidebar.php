<div class="sidebar-sticky">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $section === 'dashboard' ? 'active' : ''; ?>" href="">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $section === 'clients' ? 'active' : ''; ?>" href="">
                <i class="fas fa-users"></i>
                Clientes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $section === 'furnaces' ? 'active' : ''; ?>" href="">
                <i class="fas fa-industry"></i>
                Hornos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $section === 'inspections' ? 'active' : ''; ?>" href="">
                <i class="fas fa-clipboard-check"></i>
                Inspecciones
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $section === 'reports' ? 'active' : ''; ?>" href="">
                <i class="fas fa-file-pdf"></i>
                Reportes
            </a>
        </li>
    </ul>
</div>