document.addEventListener('DOMContentLoaded', () => {
    // Mobile Menu Toggle
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mainNav = document.querySelector('.main-menu');
    
    if (mobileBtn && mainNav) {
        mobileBtn.addEventListener('click', () => {
            if (mainNav.style.display === 'block') {
                mainNav.style.display = 'none';
            } else {
                mainNav.style.display = 'block';
                mainNav.style.width = '100%';
                mainNav.style.marginTop = '20px';
                
                const ul = mainNav.querySelector('ul');
                if (ul) {
                    ul.style.flexDirection = 'column';
                    ul.style.gap = '15px';
                    ul.style.alignItems = 'center';
                }
            }
        });
    }

    // --- API Integration Placeholder ---
    const coursesContainer = document.getElementById('courses-container');
    
    async function loadCourses() {
        if (!coursesContainer) return;

        try {
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            const fakeData = [
                { id: 1, title: 'Técnico em Enfermagem', category: 'Técnico', img: 'https://images.unsplash.com/photo-1576091160550-2173ff9e5ee5?auto=format&fit=crop&w=500&q=80' },
                { id: 2, title: 'Pedagogia EAD', category: 'Superior', img: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=500&q=80' },
                { id: 3, title: 'Gestão de RH', category: 'Superior', img: 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=500&q=80' }
            ];

            renderCourses(fakeData);
        } catch (error) {
            coursesContainer.innerHTML = '<p style="color: red; text-align: center;">Erro ao carregar cursos via API.</p>';
        }
    }

    function renderCourses(courses) {
        coursesContainer.innerHTML = '';

        courses.forEach(course => {
            const card = document.createElement('div');
            card.className = 'course-card';
            
            card.innerHTML = `
                <img src="${course.img}" alt="${course.title}">
                <div class="course-info">
                    <span>${course.category}</span>
                    <h3>${course.title}</h3>
                    <a href="#curso-${course.id}">Ver Detalhes <i class="fas fa-arrow-right" style="font-size: 12px; margin-left: 5px;"></i></a>
                </div>
            `;
            coursesContainer.appendChild(card);
        });
    }

    loadCourses();
});
