document.addEventListener('DOMContentLoaded', () => {
    console.log("Eduset App Initialized.");

    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navList = document.querySelector('.nav-list');

    if (mobileMenuBtn && navList) {
        mobileMenuBtn.addEventListener('click', () => {
            // Basic toggle for now
            if (navList.style.display === 'flex') {
                navList.style.display = 'none';
            } else {
                navList.style.display = 'flex';
                navList.style.flexDirection = 'column';
                navList.style.position = 'absolute';
                navList.style.top = '80px';
                navList.style.left = '0';
                navList.style.width = '100%';
                navList.style.backgroundColor = '#fff';
                navList.style.padding = '20px';
                navList.style.boxShadow = '0 5px 10px rgba(0,0,0,0.1)';
            }
        });
    }

    // --- API Integration Placeholder ---
    // In the future, we will fetch the courses from WordPress API
    // Example: fetch('https://eduset.com.br/wp-json/wp/v2/cursos')
    
    const coursesContainer = document.getElementById('courses-container');
    
    async function loadCourses() {
        if (!coursesContainer) return;

        try {
            // Simulando um delay de rede (remover depois quando ligar API real)
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Dados fictícios simulando o retorno da API do WordPress
            const fakeData = [
                { id: 1, title: 'Técnico em Enfermagem', category: 'Técnico', img: 'https://images.unsplash.com/photo-1576091160550-2173ff9e5ee5?auto=format&fit=crop&w=500&q=80' },
                { id: 2, title: 'Pedagogia', category: 'Superior', img: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=500&q=80' },
                { id: 3, title: 'EJA Ensino Médio', category: 'EJA', img: 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=500&q=80' }
            ];

            renderCourses(fakeData);
        } catch (error) {
            coursesContainer.innerHTML = '<p style="color: red;">Erro ao carregar os cursos.</p>';
        }
    }

    function renderCourses(courses) {
        coursesContainer.innerHTML = ''; // Limpa o loading

        courses.forEach(course => {
            const card = document.createElement('div');
            card.className = 'course-card';
            card.innerHTML = `
                <img src="${course.img}" alt="${course.title}">
                <div class="course-info">
                    <span style="font-size: 0.8rem; color: var(--secondary-color); font-weight: bold;">${course.category}</span>
                    <h3>${course.title}</h3>
                    <a href="#curso-${course.id}" class="btn-sm btn-primary">Ver Detalhes</a>
                </div>
            `;
            coursesContainer.appendChild(card);
        });
    }

    // Initiate fetch
    loadCourses();
});
