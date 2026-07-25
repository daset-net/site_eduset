/* app.js — aplicação Vue 3 do site EDUSET */
const { createApp } = Vue;

createApp({
  data() {
    return {
      scrolled: false,
      menuOpen: false,
      filtro: 'todos',
      carregando: true,
      cursos: [],
      enviando: false,
      feedback: { tipo: '', msg: '' },
      form: { nome: '', email: '', telefone: '', interesse: '', mensagem: '' }
    };
  },

  computed: {
    cursosFiltrados() {
      if (this.filtro === 'todos') return this.cursos;
      return this.cursos.filter(c => c.categoria === this.filtro);
    }
  },

  methods: {
    filtrar(cat) {
      this.filtro = cat;
      const el = document.getElementById('cursos');
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    },

    async carregarCursos() {
      try {
        const resp = await fetch('api/cursos.php');
        const data = await resp.json();
        this.cursos = data.cursos || [];
      } catch (e) {
        // Fallback caso o PHP não esteja disponível
        this.cursos = [];
      } finally {
        this.carregando = false;
      }
    },

    async enviar() {
      this.enviando = true;
      this.feedback = { tipo: '', msg: '' };
      try {
        const resp = await fetch('api/contato.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.form)
        });
        const data = await resp.json();
        if (data.ok) {
          this.feedback = { tipo: 'ok', msg: data.mensagem || 'Recebemos seu contato! Em breve retornaremos.' };
          this.form = { nome: '', email: '', telefone: '', interesse: '', mensagem: '' };
        } else {
          this.feedback = { tipo: 'err', msg: data.mensagem || 'Verifique os dados e tente novamente.' };
        }
      } catch (e) {
        this.feedback = { tipo: 'err', msg: 'Não foi possível enviar agora. Tente novamente em instantes.' };
      } finally {
        this.enviando = false;
        this.$nextTick(() => {
          const alert = document.querySelector('.form-alert');
          if (alert) alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
      }
    }
  },

  mounted() {
    // Header com efeito ao rolar
    const onScroll = () => { this.scrolled = window.scrollY > 40; };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    this.carregarCursos();

    // Animações de revelação ao rolar
    this.$nextTick(() => {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) { e.target.classList.add('in'); obs.unobserve(e.target); }
        });
      }, { threshold: 0.12 });
      document.querySelectorAll('[data-reveal]').forEach(el => obs.observe(el));
    });
  }
}).mount('#app');
