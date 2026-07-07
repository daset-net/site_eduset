document.addEventListener('DOMContentLoaded', () => {
    // Scroll Effect on Header (Glassmorphism)
    const header = document.getElementById('main-header');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Mobile Menu Toggle
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileBtn && mainNav) {
        mobileBtn.addEventListener('click', () => {
            if (mainNav.style.display === 'flex') {
                mainNav.style.display = 'none';
            } else {
                mainNav.style.display = 'flex';
                mainNav.style.flexDirection = 'column';
                mainNav.style.position = 'absolute';
                mainNav.style.top = '70px';
                mainNav.style.left = '0';
                mainNav.style.width = '100%';
                mainNav.style.background = 'var(--bg-dark)';
                mainNav.style.padding = '20px';
                mainNav.style.borderBottom = '1px solid var(--glass-border)';
            }
        });
    }

    // --- API Integration Placeholder ---
    const coursesContainer = document.getElementById('courses-container');
    
    async function loadCourses() {
        if (!coursesContainer) return;

        try {
            // Simulando carregamento API WP
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            const fakeData = [
                { id: 1, title: 'Técnico em Enfermagem', category: 'Técnico', img: 'https://images.unsplash.com/photo-1576091160550-2173ff9e5ee5?auto=format&fit=crop&w=500&q=80' },
                { id: 2, title: 'Pedagogia EAD', category: 'Superior', img: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=500&q=80' },
                { id: 3, title: 'Gestão de RH', category: 'Tecnólogo', img: 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=500&q=80' }
            ];

            renderCourses(fakeData);
        } catch (error) {
            coursesContainer.innerHTML = '<p style="color: red; text-align: center;">Erro ao carregar cursos via API.</p>';
        }
    }

    function renderCourses(courses) {
        coursesContainer.innerHTML = '';
        coursesContainer.style.display = 'grid';
        coursesContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(300px, 1fr))';
        coursesContainer.style.gap = '30px';

        courses.forEach(course => {
            const card = document.createElement('div');
            card.style.background = 'var(--glass-bg)';
            card.style.border = '1px solid var(--glass-border)';
            card.style.borderRadius = '16px';
            card.style.overflow = 'hidden';
            
            card.innerHTML = `
                <img src="${course.img}" alt="${course.title}" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 20px;">
                    <span style="font-size: 12px; color: var(--eduset-green); font-weight: 600; text-transform: uppercase;">${course.category}</span>
                    <h3 style="margin: 10px 0; font-size: 18px;">${course.title}</h3>
                    <a href="#curso" style="color: var(--eduset-blue); font-size: 14px; font-weight: 500;">Ver detalhes <i class="fas fa-arrow-right"></i></a>
                </div>
            `;
            coursesContainer.appendChild(card);
        });
    }

    loadCourses();
});
