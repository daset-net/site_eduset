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
      form: { nome: '', email: '', telefone: '', telefone_ddi: 'BR', interesse: '', mensagem: '' },
      paisesIntl: (typeof IntlPhone !== 'undefined') ? IntlPhone.paises : [],
      // Link de campanha com ?ir=matricula: leva o visitante direto à matrícula.
      irMatricula: new URLSearchParams(location.search).get('ir') === 'matricula'
    };
  },

  computed: {
    cursosFiltrados() {
      if (this.filtro === 'todos') return this.cursos;
      return this.cursos.filter(c => c.categoria === this.filtro);
    }
  },

  methods: {
    maskFoneIntl(v, ddi) {
        if (typeof IntlPhone !== 'undefined') return IntlPhone.mascaraPorPais(ddi || 'BR', v);
        return (v || '').replace(/\D/g, '').slice(0, 15);
    },
    filtrar(cat) {
      this.filtro = cat;
      const el = document.getElementById('cursos');
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    },

    // Com ?ir=matricula, o curso escolhido já abre na seção do formulário.
    linkCurso(c) {
      return 'curso.php?id=' + (c.slug || c.id) + (this.irMatricula ? '&ir=matricula' : '');
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

    // A home não tem formulário de matrícula (ele vive na página do curso), então
    // o link de campanha desce até o catálogo: o clique no curso abre a matrícula.
    if (this.irMatricula) {
      this.$nextTick(() => {
        const el = document.getElementById('cursos');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
      });
    }

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
