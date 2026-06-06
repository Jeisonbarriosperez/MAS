document.addEventListener("DOMContentLoaded", () => {
  // =========================
  // 0. Referencias a elementos
  // =========================


  const seccionInicio = document.getElementById("inicio");
  const seccionReportar = document.getElementById("reportar");
  const seccionMapa = document.getElementById("mapa");
  const seccionEstadisticas = document.getElementById("estadisticas");
  const seccionIngresar = document.getElementById("ingresar");
  const seccionEducacion = document.getElementById("educacion");


  const panelUsuario = document.getElementById("panel-usuario");
  const panelAutoridad = document.getElementById("panel-autoridad");
  const panelAdmin = document.getElementById("panel-admin");

  const formLogin = document.getElementById("form-login");
  const formRegistro = document.getElementById("form-registro");
  const formReporte = document.getElementById("form-reporte");
  const formCrearAutoridad = document.getElementById("form-crear-autoridad");

  const authTabs = document.querySelectorAll(".auth-tab");
  const panelLogin = document.getElementById("panel-login");
  const panelRegistro = document.getElementById("panel-registro");

  const eduTabs = document.querySelectorAll(".edu-tab");
  const eduPanelCiudadania = document.getElementById("edu-ciudadania");
  const eduPanelAutoridades = document.getElementById("edu-autoridades");

  const statTotal = document.getElementById("stat-total-reportes");
  const statHectareas = document.getElementById("stat-hectareas");
  const statMunicipios = document.getElementById("stat-municipios");
  const listaTipoActividad = document.getElementById("lista-tipo-actividad");
  const listaMunicipios = document.getElementById("lista-municipios");

  const tbodyAutoridad = document.getElementById("tabla-reportes-autoridad");
  const tbodyMisReportes = document.getElementById("tabla-mis-reportes");

  const detalleMiReporte = document.getElementById("detalle-mi-reporte");
  const detalleMiReporteImg = document.getElementById("detalle-mi-reporte-img");

  const detalleAutoridad = document.getElementById("detalle-reporte-autoridad");
  const detalleAutoridadImg = document.getElementById("detalle-reporte-autoridad-img");
  const estadoSelect = document.getElementById("estado-reporte-select");
  const obsTextarea = document.getElementById("observacion-cierre");
  const btnGuardarEstado = document.getElementById("btn-guardar-estado");

  const selectTipoActividad = document.getElementById("tipoActividad");
  const selectAutoridadReporte = document.getElementById("idAutoridadReporte");

  const tbodyUsuariosAdmin = document.getElementById("tabla-usuarios-admin");
  const detalleUsuarioAdmin = document.getElementById("detalle-usuario-admin");
  const formEditarUsuarioAdmin = document.getElementById("form-editar-usuario-admin");
  const btnCancelarEdicionUsuario = document.getElementById("btn-cancelar-edicion-usuario");

  const contenedorHectareas = document.getElementById("contenedor-hectareas");
  const inputHectareas = document.getElementById("hectareas");

  let usuariosAdmin = []; // lista de usuarios para el panel admin
  // =========================
  // 1. Estado global
  // =========================
  let autoridades = []; // lista completa desde el backend

  let sesionActual = null; // { id_usuario, nombre, rol }
  let reportes = [];       // TODOS los reportes para mapa + stats + autoridad/admin
  let misReportes = [];    // Solo reportes del ciudadano logueado

  let mapa = null;
  let capaMarkers = null;

  let reporteSeleccionadoAutoridad = null;
  let reporteSeleccionadoCiudadano = null;

  let reporteEnEdicion = null; // null = modo crear, objeto = modo editar
  let btnSubmitReporte = null;
  let textoOriginalBtnReporte = "";

  if (formReporte) {
    btnSubmitReporte = formReporte.querySelector('button[type="submit"]');
    if (btnSubmitReporte) {
      textoOriginalBtnReporte = btnSubmitReporte.textContent;
    }
  }

  // 2. Pestañas LOGIN / REGISTRO
  // =========================
  if (authTabs && panelLogin && panelRegistro) {
    authTabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        authTabs.forEach((t) => t.classList.remove("activo"));
        tab.classList.add("activo");

        const target = tab.dataset.target; // "login" o "registro"

        if (target === "login") {
          panelLogin.classList.remove("oculto");
          panelRegistro.classList.add("oculto");
        } else {
          panelRegistro.classList.remove("oculto");
          panelLogin.classList.add("oculto");
        }
      });
    });
  }
  // =========================
  // 3. Pestañas EDUCACIÓN AMBIENTAL
  // =========================
  if (eduTabs && eduPanelCiudadania && eduPanelAutoridades) {
    eduTabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        eduTabs.forEach((t) => t.classList.remove("activo"));
        tab.classList.add("activo");

        const target = tab.dataset.target; // "ciudadania" o "autoridades"

        if (target === "ciudadania") {
          eduPanelCiudadania.classList.remove("oculto");
          eduPanelAutoridades.classList.add("oculto");
        } else {
          eduPanelAutoridades.classList.remove("oculto");
          eduPanelCiudadania.classList.add("oculto");
        }
      });
    });
  }

  // 4. Control de VISIBILIDAD por ROL
  // =========================
  function ocultarElemento(el) {
    if (!el) return;
    el.classList.add("oculto");
    el.style.display = "none";      // ⬅ fuerza que desaparezca
  }

  function mostrarElemento(el) {
    if (!el) return;
    el.classList.remove("oculto");
    el.style.display = "block";     // ⬅ fuerza que se vea
  }



  function aplicarEstadoSesion(scrollDestino = null) {
    // === CASO 1: NO HAY SESIÓN ===
    if (!sesionActual) {
      mostrarElemento(seccionInicio);
      mostrarElemento(seccionEducacion);
      mostrarElemento(seccionIngresar);

      ocultarElemento(seccionReportar);
      ocultarElemento(seccionMapa);
      ocultarElemento(seccionEstadisticas);
      ocultarElemento(panelUsuario);
      ocultarElemento(panelAutoridad);
      ocultarElemento(panelAdmin);

      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }

    // === CASO 2: HAY SESIÓN ===
    const rol = (sesionActual.rol || "").toLowerCase();
    console.log("Rol actual:", rol);

    ocultarElemento(seccionIngresar);

    // Secciones comunes para todos los logueados
    mostrarElemento(seccionInicio);
    mostrarElemento(seccionEducacion);
    mostrarElemento(seccionReportar);   // ✅ todos ven reportar
    mostrarElemento(seccionMapa);
    mostrarElemento(seccionEstadisticas);

    // Ocultamos todos los paneles
    ocultarElemento(panelUsuario);
    ocultarElemento(panelAutoridad);
    ocultarElemento(panelAdmin);

    if (rol === "ciudadano") {
      mostrarElemento(panelUsuario);
      if (scrollDestino === "usuario" && panelUsuario) {
        panelUsuario.scrollIntoView({ behavior: "smooth" });
      }
    } else if (rol === "autoridad") {
      mostrarElemento(panelAutoridad);
      if (scrollDestino === "autoridad" && panelAutoridad) {
        panelAutoridad.scrollIntoView({ behavior: "smooth" });
      }
    } else if (rol === "admin") {
      mostrarElemento(panelAutoridad);
      mostrarElemento(panelAdmin);
      if (scrollDestino === "admin" && panelAdmin) {
        panelAdmin.scrollIntoView({ behavior: "smooth" });
      }
    }

    // ⬇ Aquí forzamos al mapa a recalcular tamaño
    inicializarMapa();
    if (mapa) {
      setTimeout(() => {
        mapa.invalidateSize();
      }, 300);
    }
  }

  // X. AUTORIDADES POR TIPO DE ACTIVIDAD
  function etiquetaEspecialidad(especialidad) {
    const map = {
      tala: "Tala de árboles",
      quema: "Quema",
      cambio_uso: "Cambio de uso del suelo",
      extraccion: "Extracción ilegal",
      otra: "Otras actividades",
      general: "General"
    };
    return map[especialidad] || especialidad;
  }

  function limpiarSelectAutoridad() {
    if (!selectAutoridadReporte) return;
    selectAutoridadReporte.innerHTML = `
      <option value="">
        Selecciona la autoridad a la que enviarás el reporte
      </option>
    `;
    selectAutoridadReporte.disabled = true;
  }

  function rellenarSelectAutoridad(lista, idSeleccionado = null) {
    if (!selectAutoridadReporte) return;

    limpiarSelectAutoridad();

    if (!Array.isArray(lista) || lista.length === 0) {
      const opt = document.createElement("option");
      opt.value = "";
      opt.textContent = "No hay autoridades para este tipo de actividad";
      selectAutoridadReporte.appendChild(opt);
      selectAutoridadReporte.disabled = true;
      return;
    }

    lista.forEach((a) => {
      const opt = document.createElement("option");
      opt.value = a.id_usuario;

      const nombreCompleto = `${a.nombre || ""} ${a.apellido || ""}`.trim();
      const etiquetaEsp = etiquetaEspecialidad(a.especialidad);
      const muni = a.municipio ? ` · ${a.municipio}` : "";

      opt.textContent =
        (nombreCompleto || `Autoridad #${a.id_usuario}`) +
        ` · ${etiquetaEsp}${muni}`;

      if (
        idSeleccionado &&
        Number(idSeleccionado) === Number(a.id_usuario)
      ) {
        opt.selected = true;
      }

      selectAutoridadReporte.appendChild(opt);
    });

    selectAutoridadReporte.disabled = false;
  }

  async function cargarAutoridadesPorTipo(tipoActividad, idSeleccionado = null) {
    if (!selectAutoridadReporte) return;

    if (!tipoActividad) {
      limpiarSelectAutoridad();
      return;
    }

    try {
      const fd = new FormData();
      fd.append("tipo_actividad", tipoActividad);

      const resp = await fetch("../php/obtener_autoridades_por_tipo.php", {
        method: "POST",
        body: fd,
      });

      const data = await resp.json();
      console.log("Autoridades para tipo:", tipoActividad, data);

      if (!data.ok) {
        alert(data.mensaje || "No se pudieron cargar las autoridades.");
        limpiarSelectAutoridad();
        return;
      }

      rellenarSelectAutoridad(data.data || [], idSeleccionado);
    } catch (error) {
      console.error("Error al cargar autoridades:", error);
      limpiarSelectAutoridad();
    }
  }

  async function cargarReportesDesdeServidor() {
    try {
      const resp = await fetch("../php/obtener_reportes.php", {
        method: "GET",
      });
      const data = await resp.json();

      if (!data.ok) {
        console.warn("No se pudieron obtener reportes:", data.mensaje);
        return;
      }

      reportes = data.data || [];

      actualizarEstadisticas();
      actualizarMarcadores();
      renderizarTablaAutoridad();
    } catch (error) {
      console.error("Error al obtener reportes:", error);
    }
  }

  // =========================
  // 6. Cargar MIS reportes 
  // =========================
  async function cargarMisReportes() {
    if (!sesionActual || sesionActual.rol !== "ciudadano") {
      misReportes = [];
      renderizarTablaUsuario();
      return;
    }

    const fd = new FormData();
    fd.append("id_usuario", sesionActual.id_usuario);

    try {
      const resp = await fetch("../php/obtener_mis_reportes.php", {
        method: "POST",
        body: fd,
      });
      const data = await resp.json();

      if (!data.ok) {
        console.warn("No se pudieron obtener mis reportes:", data.mensaje);
        misReportes = [];
      } else {
        misReportes = data.data || [];
      }

      renderizarTablaUsuario();
    } catch (error) {
      console.error("Error al obtener mis reportes:", error);
    }
  }
  async function cargarUsuariosAdmin() {
    if (!tbodyUsuariosAdmin) return;

    try {
      const resp = await fetch("../php/obtener_usuarios.php");
      const data = await resp.json();

      if (!data.ok) {
        console.warn("No se pudieron obtener usuarios:", data.mensaje);
        usuariosAdmin = [];
      } else {
        usuariosAdmin = data.data || [];
      }

      renderizarTablaUsuariosAdmin();
    } catch (error) {
      console.error("Error al obtener usuarios:", error);
      usuariosAdmin = [];
      renderizarTablaUsuariosAdmin();
    }
  }

  function renderizarTablaUsuariosAdmin() {
    if (!tbodyUsuariosAdmin) return;

    tbodyUsuariosAdmin.innerHTML = "";

    if (!usuariosAdmin.length) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
      <td colspan="7" style="text-align:center; padding:10px;">
        No hay usuarios registrados.
      </td>
    `;
      tbodyUsuariosAdmin.appendChild(tr);
      if (detalleUsuarioAdmin) detalleUsuarioAdmin.classList.add("oculto");
      return;
    }

    usuariosAdmin.forEach((u) => {
      const tr = document.createElement("tr");

      const nombreCompleto = `${u.nombre || ""} ${u.apellido || ""}`.trim();
      const especialidadLabel = etiquetaEspecialidad(u.especialidad || "");

      tr.innerHTML = `
      <td>${u.id_usuario}</td>
      <td>${nombreCompleto || "(Sin nombre)"}</td>
      <td>${u.correo || ""}</td>
      <td>${u.tipo_usuario || ""}</td>
      <td>${especialidadLabel}</td>
      <td>${u.municipio || ""}</td>
      <td>
        <button type="button" class="btn-secundario btn-editar-usuario" data-id="${u.id_usuario}">
          Editar
        </button>
        <button type="button" class="btn-peligro btn-eliminar-usuario" data-id="${u.id_usuario}">
          Eliminar
        </button>
      </td>
    `;

      tbodyUsuariosAdmin.appendChild(tr);
    });
  }
  function mostrarDetalleUsuarioAdmin(usuario) {
    if (!detalleUsuarioAdmin || !formEditarUsuarioAdmin) return;

    detalleUsuarioAdmin.classList.remove("oculto");

    document.getElementById("adminUserId").value = usuario.id_usuario;
    document.getElementById("adminUserNombre").value = usuario.nombre || "";
    document.getElementById("adminUserApellido").value = usuario.apellido || "";
    document.getElementById("adminUserCorreo").value = usuario.correo || "";
    document.getElementById("adminUserTelefono").value = usuario.telefono || "";
    document.getElementById("adminUserMunicipio").value = usuario.municipio || "";
    document.getElementById("adminUserVereda").value = usuario.vereda_barrio || "";
    document.getElementById("adminUserRol").value = (usuario.tipo_usuario || "").toLowerCase();

    const esp = usuario.especialidad || "";
    const selectEsp = document.getElementById("adminUserEspecialidad");
    if (selectEsp) {
      selectEsp.value = esp;
    }

    const inputClave = document.getElementById("adminUserNuevaClave");
    if (inputClave) {
      inputClave.value = ""; // nunca mostramos la clave actual
    }
  }
  if (tbodyUsuariosAdmin) {
    tbodyUsuariosAdmin.addEventListener("click", async (e) => {
      const btnEditar = e.target.closest(".btn-editar-usuario");
      const btnEliminar = e.target.closest(".btn-eliminar-usuario");

      if (btnEditar) {
        const id = Number(btnEditar.dataset.id);
        const usuario = usuariosAdmin.find(
          (u) => Number(u.id_usuario) === id
        );
        if (!usuario) return;
        mostrarDetalleUsuarioAdmin(usuario);
        return;
      }

      if (btnEliminar) {
        const id = Number(btnEliminar.dataset.id);

        const confirmar = confirm(
          "¿Seguro que deseas eliminar este usuario? Esta acción no se puede deshacer."
        );
        if (!confirmar) return;

        const fd = new FormData();
        fd.append("id_usuario", id);

        try {
          const resp = await fetch("../php/eliminar_usuario.php", {
            method: "POST",
            body: fd,
          });
          const data = await resp.json();

          alert(data.mensaje || "Respuesta del servidor.");

          if (data.ok) {
            await cargarUsuariosAdmin();
          }
        } catch (error) {
          console.error("Error al eliminar usuario:", error);
          alert("Error de conexión con el servidor al eliminar el usuario.");
        }
      }
    });
  }
  if (formEditarUsuarioAdmin) {
    formEditarUsuarioAdmin.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (!sesionActual || sesionActual.rol !== "admin") {
        alert("Solo el administrador puede actualizar usuarios.");
        return;
      }

      const fd = new FormData(formEditarUsuarioAdmin);

      try {
        const resp = await fetch("../php/actualizar_usuario.php", {
          method: "POST",
          body: fd,
        });
        const data = await resp.json();

        alert(data.mensaje || "Respuesta del servidor.");

        if (data.ok) {
          detalleUsuarioAdmin.classList.add("oculto");
          await cargarUsuariosAdmin();
        }
      } catch (error) {
        console.error("Error al actualizar usuario:", error);
        alert("Error de conexión con el servidor al actualizar el usuario.");
      }
    });
  }

  if (btnCancelarEdicionUsuario && detalleUsuarioAdmin) {
    btnCancelarEdicionUsuario.addEventListener("click", () => {
      detalleUsuarioAdmin.classList.add("oculto");
    });
  }

  // 7. LOGIN
  if (formLogin) {
    formLogin.addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(formLogin);

      try {
        const resp = await fetch("../php/login.php", {
          method: "POST",
          body: formData,
        });

        const data = await resp.json();
        console.log("Respuesta login:", data);

        if (!data.ok) {
          alert(data.mensaje || "Error al iniciar sesión.");
          return;
        }

        // Guardamos la sesión en memoria JS
        sesionActual = {
          id_usuario: data.id_usuario,
          nombre: data.nombre,
          rol: (data.rol || "").toLowerCase()  // 👈 aseguramos minúsculas
        };

        alert(
          `Bienvenido, ${sesionActual.nombre} (${sesionActual.rol}). Sesión iniciada.`
        );
        console.log("SesionActual en JS:", sesionActual);

        // Decidir hacia dónde hacer scroll según el rol
        let destino = null;
        if (sesionActual.rol === "ciudadano") destino = "usuario";
        else if (sesionActual.rol === "autoridad") destino = "autoridad";
        else if (sesionActual.rol === "admin") destino = "admin";

        // Aplicar visibilidad según rol (esto ya muestra REPORTAR para todos)
        aplicarEstadoSesion(destino);

        // Cargamos reportes generales (mapa, stats, autoridad/admin)
        await cargarReportesDesdeServidor();

        // Si es ciudadano, también cargamos sus propios reportes
        if (sesionActual.rol === "ciudadano") {
          await cargarMisReportes();
        }
        if (sesionActual.rol === "admin") {
          await cargarUsuariosAdmin();
        }
        if (sesionActual.rol === "autoridad") {
          await cargarUsuariosAdmin();
        }

      } catch (error) {
        console.error("Error en login:", error);
        alert("Error de conexión con el servidor al iniciar sesión.");
      }
    });
  }

  // 8. REGISTRO (solo ciudadanos)
  // =========================
  if (formRegistro) {
    formRegistro.addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(formRegistro);

      const clave = formData.get("regClave");
      const clave2 = formData.get("regClave2");

      if (!clave || !clave2) {
        alert("Debes ingresar y confirmar la contraseña.");
        return;
      }
      if (clave !== clave2) {
        alert("Las contraseñas no coinciden.");
        return;
      }

      try {
        const resp = await fetch("../php/registrar_usuario.php", {
          method: "POST",
          body: formData,
        });
        const data = await resp.json();

        alert(data.mensaje || "Respuesta del servidor.");

        if (data.ok) {
          formRegistro.reset();
          // Cambiamos a la pestaña de LOGIN
          if (authTabs.length >= 2) {
            authTabs.forEach((t) => t.classList.remove("activo"));
            authTabs[0].classList.add("activo");
          }
          if (panelLogin && panelRegistro) {
            panelLogin.classList.remove("oculto");
            panelRegistro.classList.add("oculto");
          }
        }
      } catch (error) {
        console.error("Error en registro:", error);
        alert("Error de conexión con el servidor al registrar.");
      }
    });
  }

  // =========================
  // 9. ESTADÍSTICAS
  // =========================
  function actualizarEstadisticas() {
    if (
      !statTotal ||
      !statHectareas ||
      !statMunicipios ||
      !listaTipoActividad ||
      !listaMunicipios
    ) {
      return;
    }

    const totalReportes = reportes.length;

    let totalHectareas = 0;
    const setMunicipios = new Set();
    const conteoPorTipo = {
      tala: 0,
      quema: 0,
      cambio_uso: 0,
      extraccion: 0,
      otra: 0,
    };
    const conteoPorMunicipio = {};

    reportes.forEach((r) => {
      const valor = Number(r.hectareas_afectadas);
      if (!isNaN(valor)) {
        totalHectareas += valor;
      }

      if (r.municipio) {
        setMunicipios.add(r.municipio);
        conteoPorMunicipio[r.municipio] =
          (conteoPorMunicipio[r.municipio] || 0) + 1;
      }

      const tipo = r.tipo_actividad || "otra";
      if (conteoPorTipo[tipo] !== undefined) {
        conteoPorTipo[tipo] += 1;
      } else {
        conteoPorTipo.otra += 1;
      }
    });

    statTotal.textContent = totalReportes;
    statHectareas.textContent = totalHectareas.toFixed(2);
    statMunicipios.textContent = setMunicipios.size;

    listaTipoActividad.innerHTML = "";
    const etiquetasTipo = {
      tala: "Tala de árboles",
      quema: "Quema",
      cambio_uso: "Cambio de uso del suelo",
      extraccion: "Extracción ilegal",
      otra: "Otra actividad",
    };

    Object.keys(conteoPorTipo).forEach((tipo) => {
      const li = document.createElement("li");
      li.innerHTML = `
        <span class="stat-label">${etiquetasTipo[tipo]}</span>
        <span class="stat-badge">${conteoPorTipo[tipo]}</span>
      `;
      listaTipoActividad.appendChild(li);
    });

    listaMunicipios.innerHTML = "";
    Object.keys(conteoPorMunicipio).forEach((muni) => {
      const li = document.createElement("li");
      li.innerHTML = `
        <span class="stat-label">${muni}</span>
        <span class="stat-badge">${conteoPorMunicipio[muni]}</span>
      `;
      listaMunicipios.appendChild(li);
    });
  }

  // =========================
  // 🔟 TABLA PANEL AUTORIDAD
  // =========================
  function renderizarTablaAutoridad() {
    if (!tbodyAutoridad) return;

    tbodyAutoridad.innerHTML = "";

    // Si el usuario logueado es autoridad: solo ver sus reportes asignados
    let lista = reportes;
    if (sesionActual && sesionActual.rol === "autoridad") {
      lista = reportes.filter(
        (r) => Number(r.id_autoridad) === Number(sesionActual.id_usuario)
      );
    }

    if (!lista || lista.length === 0) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td colspan="8" style="text-align:center; padding:10px;">
          No hay reportes asignados para esta autoridad.
        </td>
      `;
      tbodyAutoridad.appendChild(tr);

      if (detalleAutoridad) {
        detalleAutoridad.classList.add("oculto");
      }
      return;
    }

    lista.forEach((r) => {
      const tr = document.createElement("tr");

      const nombreCiudadano = `${r.ciudadano_nombre || ""} ${r.ciudadano_apellido || ""
        }`.trim();

      tr.innerHTML = `
        <td>${r.id_reporte}</td>
        <td>${nombreCiudadano}</td>
        <td>${r.municipio || ""}</td>
        <td>${r.vereda_zona || ""}</td>
        <td>${r.tipo_actividad || ""}</td>
        <td>${r.fecha_observacion || ""}</td>
        <td>${r.estado_reporte || "registrado"}</td>
        <td>
          <button type="button" class="btn-secundario btn-ver-reporte" data-id="${r.id_reporte}">
            Ver detalle
          </button>
        </td>
      `;

      tbodyAutoridad.appendChild(tr);
    });
  }


  function mostrarDetalleAutoridad(reporte) {
    if (!detalleAutoridad) return;

    reporteSeleccionadoAutoridad = reporte;

    detalleAutoridad.classList.remove("oculto");

    const mapCampo = (campo, valor) => {
      const span = detalleAutoridad.querySelector(`[data-campo="${campo}"]`);
      if (span) span.textContent = valor || "";
    };

    const nombreCiudadano = `${reporte.ciudadano_nombre || ""} ${reporte.ciudadano_apellido || ""
      }`.trim();

    mapCampo("ciudadano", nombreCiudadano);
    mapCampo("correo", reporte.ciudadano_correo || "");
    mapCampo("municipio", reporte.municipio || "");
    mapCampo("vereda", reporte.vereda_zona || "");
    mapCampo("tipo", reporte.tipo_actividad || "");
    mapCampo(
      "fecha-hora",
      reporte.fecha_observacion +
      (reporte.hora_observacion ? " " + reporte.hora_observacion : "")
    );
    mapCampo("hectareas", reporte.hectareas_afectadas || "No especificado");
    mapCampo("ecosistema", reporte.ecosistema || "No especificado");
    mapCampo("descripcion", reporte.descripcion || "");

    if (detalleAutoridadImg) {
      if (reporte.evidencia_foto) {
        detalleAutoridadImg.src = "../" + reporte.evidencia_foto;
        detalleAutoridadImg.style.display = "block";
      } else {
        detalleAutoridadImg.src = "";
        detalleAutoridadImg.style.display = "none";
      }
    }

    if (estadoSelect) {
      estadoSelect.value = reporte.estado_reporte || "registrado";
    }
    if (obsTextarea) {
      obsTextarea.value = reporte.observacion_cierre || "";
    }
  }

  if (tbodyAutoridad) {
    tbodyAutoridad.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-ver-reporte");
      if (!btn) return;

      const id = Number(btn.dataset.id);
      const reporte = reportes.find(
        (r) => Number(r.id_reporte) === Number(id)
      );
      if (!reporte) return;

      mostrarDetalleAutoridad(reporte);
    });
  }

  if (btnGuardarEstado) {
    btnGuardarEstado.addEventListener("click", async () => {
      if (
        !sesionActual ||
        (sesionActual.rol !== "autoridad" && sesionActual.rol !== "admin")
      ) {
        alert("Solo las autoridades o el administrador pueden actualizar el estado.");
        return;
      }

      if (!reporteSeleccionadoAutoridad) {
        alert("Selecciona primero un reporte en la tabla.");
        return;
      }

      const nuevoEstado = estadoSelect ? estadoSelect.value : "registrado";
      const observacion = obsTextarea ? obsTextarea.value.trim() : "";

      const fd = new FormData();
      fd.append("id_reporte", reporteSeleccionadoAutoridad.id_reporte);
      fd.append("id_autoridad", sesionActual.id_usuario);
      fd.append("estado", nuevoEstado);
      fd.append("observacion", observacion);

      try {
        const resp = await fetch("../php/actualizar_estado_reporte.php", {
          method: "POST",
          body: fd,
        });
        const data = await resp.json();

        alert(data.mensaje || "Respuesta del servidor.");

        if (data.ok) {
          await cargarReportesDesdeServidor();
          const actualizado = reportes.find(
            (r) =>
              Number(r.id_reporte) ===
              Number(reporteSeleccionadoAutoridad.id_reporte)
          );
          if (actualizado) {
            mostrarDetalleAutoridad(actualizado);
          }
        }
      } catch (error) {
        console.error("Error al actualizar estado del reporte:", error);
        alert("Error de conexión con el servidor al actualizar el reporte.");
      }
    });
  }

  // =========================
  // 1️⃣1. TABLA PANEL CIUDADANO (MIS REPORTES)
  // =========================
  function renderizarTablaUsuario() {
    if (!tbodyMisReportes) return;

    tbodyMisReportes.innerHTML = "";

    misReportes.forEach((r) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
      <td>${r.id_reporte}</td>
      <td>${r.fecha_observacion || ""}</td>
      <td>${r.municipio || ""}</td>
      <td>${r.tipo_actividad || ""}</td>
      <td>${r.estado_reporte || "registrado"}</td>
      <td>
        <button type="button" class="btn-secundario btn-ver-mi-reporte" data-id="${r.id_reporte}">
          Ver
        </button>
        <button type="button" class="btn-secundario btn-editar-mi-reporte" data-id="${r.id_reporte}">
          Editar
        </button>
        <button type="button" class="btn-peligro btn-eliminar-mi-reporte" data-id="${r.id_reporte}">
          Eliminar
        </button>
      </td>
    `;
      tbodyMisReportes.appendChild(tr);
    });

    if (misReportes.length === 0 && detalleMiReporte) {
      detalleMiReporte.classList.add("oculto");
    }
  }


  function mostrarDetalleMiReporte(reporte) {
    if (!detalleMiReporte) return;

    reporteSeleccionadoCiudadano = reporte;

    detalleMiReporte.classList.remove("oculto");

    const mapCampo = (campo, valor) => {
      const span = detalleMiReporte.querySelector(`[data-campo="${campo}"]`);
      if (span) span.textContent = valor || "";
    };

    mapCampo("municipio", reporte.municipio || "");
    mapCampo("vereda", reporte.vereda_zona || "");
    mapCampo("tipo", reporte.tipo_actividad || "");
    mapCampo(
      "fecha-hora",
      reporte.fecha_observacion +
      (reporte.hora_observacion ? " " + reporte.hora_observacion : "")
    );
    mapCampo("hectareas", reporte.hectareas_afectadas || "No especificado");
    mapCampo("ecosistema", reporte.ecosistema || "No especificado");
    mapCampo("descripcion", reporte.descripcion || "");

    if (detalleMiReporteImg) {
      if (reporte.evidencia_foto) {
        detalleMiReporteImg.src = "../" + reporte.evidencia_foto;
        detalleMiReporteImg.style.display = "block";
      } else {
        detalleMiReporteImg.src = "";
        detalleMiReporteImg.style.display = "none";
      }
    }
  }

  if (tbodyMisReportes) {
    tbodyMisReportes.addEventListener("click", async (e) => {
      const btnVer = e.target.closest(".btn-ver-mi-reporte");
      const btnEditar = e.target.closest(".btn-editar-mi-reporte");
      const btnEliminar = e.target.closest(".btn-eliminar-mi-reporte");

      if (btnVer) {
        const id = Number(btnVer.dataset.id);
        const reporte = misReportes.find(
          (r) => Number(r.id_reporte) === Number(id)
        );
        if (reporte) {
          mostrarDetalleMiReporte(reporte);
        }
        return;
      }

      if (btnEditar) {
        if (!sesionActual || sesionActual.rol !== "ciudadano") {
          alert("Solo el ciudadano dueño del reporte puede editarlo.");
          return;
        }

        const id = Number(btnEditar.dataset.id);
        const reporte = misReportes.find(
          (r) => Number(r.id_reporte) === Number(id)
        );
        if (!reporte) return;

        // Entramos en modo edición
        reporteEnEdicion = reporte;
        if (btnSubmitReporte) {
          btnSubmitReporte.textContent = "Guardar cambios";
        }

        // Rellenar el formulario de reportar
        document.getElementById("tipoActividad").value =
          reporte.tipo_actividad || "";
        document.getElementById("municipio").value = reporte.municipio || "";
        document.getElementById("vereda").value = reporte.vereda_zona || "";
        document.getElementById("coordenadas").value =
          reporte.coordenadas || "";
        document.getElementById("fecha").value =
          reporte.fecha_observacion || "";
        document.getElementById("hora").value =
          reporte.hora_observacion || "";
        document.getElementById("hectareas").value =
          reporte.hectareas_afectadas || "";
        document.getElementById("ecosistema").value =
          reporte.ecosistema || "";
        document.getElementById("descripcion").value =
          reporte.descripcion || "";

        // El input file de evidencia NO se puede rellenar por seguridad,
        // si el usuario quiere cambiar la foto, selecciona una nueva.

        // Llevar al usuario a la sección de reporte
        if (seccionReportar) {
          seccionReportar.scrollIntoView({ behavior: "smooth" });
        }

        alert("Estás editando el reporte ID " + reporte.id_reporte);
        return;
      }

      if (btnEliminar) {
        if (!sesionActual || sesionActual.rol !== "ciudadano") {
          alert("Solo el ciudadano dueño del reporte puede eliminarlo.");
          return;
        }

        const id = Number(btnEliminar.dataset.id);
        const confirmar = confirm(
          "¿Seguro que deseas eliminar este reporte? Esta acción no se puede deshacer."
        );
        if (!confirmar) return;

        const fd = new FormData();
        fd.append("id_reporte", id);
        fd.append("id_usuario", sesionActual.id_usuario);

        try {
          const resp = await fetch("../php/eliminar_reporte.php", {
            method: "POST",
            body: fd,
          });
          const data = await resp.json();

          alert(data.mensaje || "Respuesta del servidor.");

          if (data.ok) {
            await cargarMisReportes();
            await cargarReportesDesdeServidor();
          }
        } catch (error) {
          console.error("Error al eliminar reporte:", error);
          alert("Error de conexión con el servidor al eliminar el reporte.");
        }
      }
    });
  }

  // 1️⃣2. PANEL ADMIN: crear autoridad
  // =========================
  if (formCrearAutoridad) {
    formCrearAutoridad.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (!sesionActual || sesionActual.rol !== "admin") {
        alert("Solo el administrador puede crear autoridades.");
        return;
      }

      const fd = new FormData(formCrearAutoridad);
      fd.append("creado_por", sesionActual.id_usuario);

      try {
        const resp = await fetch("../php/crear_autoridad.php", {
          method: "POST",
          body: fd,
        });
        const data = await resp.json();

        alert(data.mensaje || "Respuesta del servidor.");

        if (data.ok) {
          formCrearAutoridad.reset();
        }
      } catch (error) {
        console.error("Error al crear autoridad:", error);
        alert("Error de conexión con el servidor al crear la autoridad.");
      }
    });
  }

  // =========================
  // 1️⃣3. MAPA (Leaflet)
  // =========================
  function inicializarMapa() {
    const divMapa = document.getElementById("mapa-zonas");
    if (!divMapa) return;
    if (typeof L === "undefined") return; // Leaflet aún no cargó

    if (mapa) return; // ya estaba creado

    mapa = L.map("mapa-zonas", {
      scrollWheelZoom: false,
    }).setView([9.315, -75.4], 8);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 18,
      attribution: "Datos del mapa © OpenStreetMap colaboradores",
    }).addTo(mapa);

    capaMarkers = L.layerGroup().addTo(mapa);

    actualizarMarcadores();
  }

  function obtenerLatLng(reporte) {
    // Si el reporte tiene coordenadas tipo "9.31, -75.40"
    if (reporte.coordenadas) {
      const partes = String(reporte.coordenadas).split(",");
      if (partes.length !== 2) return null;

      const lat = parseFloat(partes[0].trim());
      const lon = parseFloat(partes[1].trim());
      if (isNaN(lat) || isNaN(lon)) return null;

      return [lat, lon];
    }

    // Si NO hay coordenadas, por ahora no ponemos marcador
    return null;
  }

  function actualizarMarcadores() {
    if (!mapa || !capaMarkers) return;

    capaMarkers.clearLayers();

    reportes.forEach((r) => {
      const latLng = obtenerLatLng(r);
      if (!latLng) return; // sin coordenadas -> sin marcador

      const popupHtml = `
      <strong>${(r.tipo_actividad || "").toUpperCase()}</strong><br/>
      <span>${r.municipio || ""} - ${r.vereda_zona || ""}</span><br/>
      <span>Fecha: ${r.fecha_observacion || ""}</span><br/>
      <span>Ha afectadas: ${r.hectareas_afectadas ? r.hectareas_afectadas : "No especificado"
        }</span>
    `;

      L.marker(latLng).addTo(capaMarkers).bindPopup(popupHtml);
    });
  }


  //  FORMULARIO REPORTE (solo ciudadano)

  if (formReporte) {
    formReporte.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (!sesionActual) {
        alert("Debes iniciar sesión para registrar un reporte.");
        return;
      }

      const tipoActividad = document.getElementById("tipoActividad").value;
      const municipio = document.getElementById("municipio").value.trim();
      const vereda = document.getElementById("vereda").value.trim();
      const coordenadas = document.getElementById("coordenadas").value.trim();
      const fecha = document.getElementById("fecha").value;
      const hora = document.getElementById("hora").value;
      const hectareas = document.getElementById("hectareas").value;
      const ecosistema = document.getElementById("ecosistema").value;
      const descripcion = document
        .getElementById("descripcion")
        .value.trim();
      const idAutoridad = document.getElementById("idAutoridadReporte").value;

      if (!tipoActividad || !municipio || !vereda || !fecha || !descripcion || !idAutoridad) {
        alert("Por favor completa los campos obligatorios del reporte.");
        return;
      }

      const formData = new FormData(formReporte);
      formData.append("id_usuario", sesionActual.id_usuario);
      formData.append("id_autoridad", idAutoridad);

      try {
        const resp = await fetch("../php/crear_reporte.php", {
          method: "POST",
          body: formData,
        });
        const data = await resp.json();

        alert(data.mensaje || "Respuesta del servidor.");

        if (data.ok) {
          formReporte.reset();
          await cargarReportesDesdeServidor();
          await cargarMisReportes();
        }
      } catch (error) {
        console.error("Error al enviar reporte:", error);
        alert("Error de conexión con el servidor al registrar el reporte.");
      }
    });
  }

  // 1️⃣5. INICIO
  // 1️⃣5. INICIO
  aplicarEstadoSesion();

  // Cuando el usuario cambie el tipo de actividad, cargamos autoridades
  if (selectTipoActividad && selectAutoridadReporte) {
    limpiarSelectAutoridad();  // estado inicial

    selectTipoActividad.addEventListener("change", () => {
      const tipo = selectTipoActividad.value;
      cargarAutoridadesPorTipo(tipo);
    });
  }


});

