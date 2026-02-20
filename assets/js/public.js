/**
 * JavaScript público del plugin Event Show
 *
 * @package Event_Show
 */

(function ($) {
  "use strict";

  // Previsualización de imagen banner
  $(document).on("change", "#event_banner", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#event_banner_preview").attr("src", e.target.result).show();
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#event_banner_preview").hide();
    }
  });

  // Previsualización de miniatura
  $(document).on("change", "#event_thumbnail", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#event_thumbnail_preview").attr("src", e.target.result).show();
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#event_thumbnail_preview").hide();
    }
  });

  // Previsualización de banner en modal de edición
  $(document).on("change", "#edit_event_banner", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#edit_banner_preview").html(
          '<img src="' +
            e.target.result +
            '" style="max-width:100%; height:auto; border-radius:8px;">',
        );
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#edit_banner_preview").html("");
    }
  });

  // Previsualización de imagen grid en modal de edición
  $(document).on("change", "#edit_event_grid_image", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#edit_grid_image_preview").html(
          '<img src="' +
            e.target.result +
            '" style="max-width:100%; height:auto; border-radius:8px;">',
        );
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#edit_grid_image_preview").html("");
    }
  });

  /**
   * Inicializar cuando el DOM esté listo
   */
  $(document).ready(function () {
    // Formulario de registro de asistentes
    initRegistrationForm();

    // Modal de registro (shortcode)
    initRegistrationModal();

    // Formulario de envío de eventos
    initSubmitEventForm();

    // Carrusel
    initCarousel();

    // Slider
    initSlider();

    // Dashboard tabs
    initDashboardTabs();

    // Botones compartir y calendario
    initShareButtons();

    // Datepicker
    initDatepicker();

    // Datetime picker para campos combinados de fecha/hora
    initDatetimePicker();

    // Modales
    initModals();

    // Paginación para Grid y List
    initEventPagination();

    // Modal editar evento
    $(document).on("click", ".edit-event-link", function (e) {
      e.preventDefault();
      var eventId = $(this).data("event-id");

      // Obtener datos del evento via AJAX
      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: {
          action: "event_show_get_event_data",
          event_id: eventId,
          nonce: eventShowData.nonce,
        },
        success: function (response) {
          if (response.success) {
            $("#edit_event_id").val(eventId);
            $("#edit_event_description").val(response.data.description);

            // Convertir fecha y hora a formato datetime-local (YYYY-MM-DDTHH:MM)
            if (response.data.event_date) {
              var dateParts = response.data.event_date.split("/"); // DD/MM/YYYY
              var timeParts = response.data.event_time
                ? response.data.event_time.split(":")
                : ["00", "00"];

              if (dateParts.length === 3) {
                var datetimeStr =
                  dateParts[2] +
                  "-" +
                  dateParts[1].padStart(2, "0") +
                  "-" +
                  dateParts[0].padStart(2, "0") +
                  "T" +
                  timeParts[0].padStart(2, "0") +
                  ":" +
                  timeParts[1].padStart(2, "0");
                $("#edit_event_datetime_start").val(datetimeStr);
              }
            }

            // Convertir fecha y hora de fin a formato datetime-local
            if (response.data.event_end_date) {
              var endDateParts = response.data.event_end_date.split("/"); // DD/MM/YYYY
              var endTimeParts = response.data.event_end_time
                ? response.data.event_end_time.split(":")
                : ["00", "00"];

              if (endDateParts.length === 3) {
                var endDatetimeStr =
                  endDateParts[2] +
                  "-" +
                  endDateParts[1].padStart(2, "0") +
                  "-" +
                  endDateParts[0].padStart(2, "0") +
                  "T" +
                  endTimeParts[0].padStart(2, "0") +
                  ":" +
                  endTimeParts[1].padStart(2, "0");
                $("#edit_event_datetime_end").val(endDatetimeStr);
              }
            }

            $("#edit_event_category").val(response.data.category || "");
            $("#edit_event_age").val(response.data.age_classification || "");
            $("#edit_event_location").val(response.data.location || "");
            $("#edit_event_capacity").val(response.data.max_attendees || "");

            // Mostrar previsualizaciones de imágenes existentes
            if (response.data.banner_url) {
              $("#edit_banner_preview").html(
                '<img src="' +
                  response.data.banner_url +
                  '" style="max-width:100%; height:auto; border-radius:8px;">',
              );
            } else {
              $("#edit_banner_preview").html("");
            }

            if (response.data.grid_image_url) {
              $("#edit_grid_image_preview").html(
                '<img src="' +
                  response.data.grid_image_url +
                  '" style="max-width:100%; height:auto; border-radius:8px;">',
              );
            } else {
              $("#edit_grid_image_preview").html("");
            }

            $("#edit-event-modal").fadeIn();
          } else {
            alert(response.data.message || "Error al cargar evento");
          }
        },
      });
    });

    // Modal editar organizador
    $(document).on("click", ".edit-organizer-link", function (e) {
      e.preventDefault();
      var $btn = $(this);
      $("#edit_org_id").val($btn.data("org-id"));
      $("#edit_org_name").val($btn.data("org-name"));
      $("#edit_org_email").val($btn.data("org-email"));
      $("#edit_org_phone").val($btn.data("org-phone"));
      $("#edit_org_website").val($btn.data("org-website"));
      // Imagen/logo
      var imgId = $btn.data("org-image") || "";
      var imgUrl = "";
      if (imgId) {
        imgUrl = $btn.closest(".dashboard-card").find("img").attr("src") || "";
      }
      $("#edit_org_image").val(imgId);
      if (imgUrl) {
        $("#edit_org_image_preview").attr("src", imgUrl).show();
        $("#edit_org_image_remove").show();
      } else {
        $("#edit_org_image_preview").hide();
        $("#edit_org_image_remove").hide();
      }
      $("#edit-organizer-modal").fadeIn();
    });

    // Vista previa de imagen seleccionada
    $(document).on("change", "#edit_org_image_file", function (e) {
      var input = this;
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $("#edit_org_image_preview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
        // Limpiar el campo oculto de ID de imagen si se selecciona una nueva
        $("#edit_org_image").val("");
      } else {
        $("#edit_org_image_preview").hide();
      }
    });

    // Countdown
    initCountdown();
  });

  /**
   * Inicializar formulario de registro
   */
  function initRegistrationForm() {
    $("#event-submit-form").on("submit", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $form.find(".event-submit-btn");
      var $messages = $form.find(".form-messages");

      // Convertir datetime-local a formatos separados
      var datetimeStart = $("#event_datetime_start").val();

      console.log("Datetime Start value:", datetimeStart); // Debug

      if (!datetimeStart) {
        $messages
          .removeClass("success")
          .addClass("error")
          .html(
            "⚠️ Por favor, selecciona la fecha y hora de inicio del evento.",
          )
          .show();
        $("html, body").animate(
          { scrollTop: $messages.offset().top - 100 },
          500,
        );
        return false;
      }

      if (datetimeStart) {
        var dtStart = new Date(datetimeStart);

        // Verificar que la fecha sea válida
        if (isNaN(dtStart.getTime())) {
          console.error("Invalid date:", datetimeStart);
          $messages
            .removeClass("success")
            .addClass("error")
            .html(
              "⚠️ La fecha de inicio no es válida. Por favor, selecciona una fecha correcta.",
            )
            .show();
          $("html, body").animate(
            { scrollTop: $messages.offset().top - 100 },
            500,
          );
          return false;
        }

        var dateStr =
          String(dtStart.getDate()).padStart(2, "0") +
          "/" +
          String(dtStart.getMonth() + 1).padStart(2, "0") +
          "/" +
          dtStart.getFullYear();
        var timeStr =
          String(dtStart.getHours()).padStart(2, "0") +
          ":" +
          String(dtStart.getMinutes()).padStart(2, "0");

        $("#event_date_submit").val(dateStr);
        $("#event_time_submit").val(timeStr);

        console.log("Date converted:", dateStr, "Time:", timeStr); // Debug
      }

      var datetimeEnd = $("#event_datetime_end").val();
      if (datetimeEnd) {
        var dtEnd = new Date(datetimeEnd);

        // Verificar que la fecha sea válida
        if (!isNaN(dtEnd.getTime())) {
          var dateEndStr =
            String(dtEnd.getDate()).padStart(2, "0") +
            "/" +
            String(dtEnd.getMonth() + 1).padStart(2, "0") +
            "/" +
            dtEnd.getFullYear();
          var timeEndStr =
            String(dtEnd.getHours()).padStart(2, "0") +
            ":" +
            String(dtEnd.getMinutes()).padStart(2, "0");
          $("#event_date_end").val(dateEndStr);
          $("#event_time_end").val(timeEndStr);

          console.log("End date converted:", dateEndStr, "Time:", timeEndStr); // Debug
        }
      }

      // Validar fecha de evento
      if (datetimeStart) {
        var minDaysAdvance =
          eventShowData && eventShowData.minDaysAdvance
            ? parseInt(eventShowData.minDaysAdvance)
            : 5;

        var selectedDate = new Date(datetimeStart);
        var minDate = new Date();
        minDate.setDate(minDate.getDate() + minDaysAdvance);
        minDate.setHours(0, 0, 0, 0);
        selectedDate.setHours(0, 0, 0, 0);

        if (selectedDate < minDate) {
          var minDateStr =
            String(minDate.getDate()).padStart(2, "0") +
            "/" +
            String(minDate.getMonth() + 1).padStart(2, "0") +
            "/" +
            minDate.getFullYear();

          $messages
            .removeClass("success")
            .addClass("error")
            .html(
              "⚠️ La fecha del evento debe ser al menos " +
                minDaysAdvance +
                " día" +
                (minDaysAdvance > 1 ? "s" : "") +
                " a partir de hoy. Fecha mínima permitida: " +
                minDateStr +
                ".",
            )
            .show();
          $("html, body").animate(
            {
              scrollTop: $messages.offset().top - 100,
            },
            500,
          );
          return false;
        }
      }

      // Validación final antes de enviar
      var finalEventDate = $("#event_date_submit").val();
      var finalEventTime = $("#event_time_submit").val();

      console.log(
        "Final values before submit - Date:",
        finalEventDate,
        "Time:",
        finalEventTime,
      ); // Debug

      if (!finalEventDate || !finalEventTime) {
        $messages
          .removeClass("success")
          .addClass("error")
          .html(
            "⚠️ Error al procesar la fecha del evento. Por favor, intenta nuevamente.",
          )
          .show();
        $("html, body").animate(
          { scrollTop: $messages.offset().top - 100 },
          500,
        );
        return false;
      }

      $btn.prop("disabled", true);
      $btn.find(".btn-text").hide();
      $btn.find(".btn-loading").show();

      var formData = new FormData();

      // Agregar todos los campos del formulario explícitamente
      formData.append("action", "event_show_submit_event");
      formData.append("nonce", $form.find('[name="nonce"]').val());
      formData.append("title", $("#event_title").val() || "");
      formData.append("description", $("#event_description").val() || "");
      // Leer valores de fecha/hora inicio
      formData.append("event_date", finalEventDate);
      formData.append("event_time", finalEventTime);
      formData.append("event_date_end", $("#event_date_end").val() || "");
      formData.append("event_time_end", $("#event_time_end").val() || "");

      formData.append("category", $("#event_category").val() || "");
      formData.append("age_rating", $("#event_age_rating").val() || "");

      // Agregar organizador (verificar ambos posibles campos)
      var organizer_id = $("#event_organizer_id").val() || "";
      var organizer = $("#event_organizer").val() || "";
      formData.append("organizer_id", organizer_id);
      formData.append("organizer", organizer);

      // Agregar lugar - manejar el caso especial de usar el establecimiento como lugar
      var eventLocationValue = $("#event_location").val() || "";
      if (eventLocationValue === "USE_ORGANIZER_AS_LOCATION" && organizer_id) {
        // Enviar el establecimiento como lugar
        formData.append("location", "");
        formData.append("location_establecimiento_id", organizer_id);
      } else {
        formData.append("location", eventLocationValue);
        formData.append("location_establecimiento_id", "");
      }

      // Adjuntar archivos
      var banner = $("#event_banner")[0].files[0];
      var thumb = $("#event_thumbnail")[0].files[0];
      if (banner) formData.append("event_banner", banner);
      if (thumb) formData.append("event_thumbnail", thumb);

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $messages
              .removeClass("error")
              .addClass("success")
              .html(response.data.message)
              .show();
            $form[0].reset();
            $("#event_banner_preview, #event_thumbnail_preview").hide();

            // Cerrar modal si existe y recargar la página
            var $modal = $form.closest(".event-modal-overlay, .event-modal");
            if ($modal.length) {
              setTimeout(function () {
                $modal.fadeOut(300, function () {
                  window.location.reload();
                });
              }, 1500);
            } else {
              // Si no hay modal, recargar después del mensaje
              setTimeout(function () {
                window.location.reload();
              }, 2000);
            }
          } else {
            $messages
              .removeClass("success")
              .addClass("error")
              .html(response.data.message)
              .show();
          }
        },
        error: function () {
          $messages
            .removeClass("success")
            .addClass("error")
            .html(eventShowData.i18n.error)
            .show();
        },
        complete: function () {
          $btn.prop("disabled", false);
          $btn.find(".btn-text").show();
          $btn.find(".btn-loading").hide();

          // Scroll a mensajes
          $("html, body").animate(
            {
              scrollTop: $messages.offset().top - 100,
            },
            500,
          );
        },
      });
    });
  }

  /**
   * Inicializar modal de registro (shortcode)
   */
  function initRegistrationModal() {
    // Abrir modal al hacer clic en el botón trigger
    $(document).on("click", ".event-registration-trigger-btn", function (e) {
      e.preventDefault();
      var modalId = $(this).data("modal-id");
      var $modal = $("#" + modalId);

      if ($modal.length) {
        $modal.fadeIn(200);
        $("body").addClass("modal-open");

        // Limpiar mensajes previos
        $modal.find(".form-messages").removeClass("success error").hide();
      }
    });

    // Cerrar modal al hacer clic en el botón de cerrar
    $(document).on("click", ".event-registration-modal-close", function (e) {
      e.preventDefault();
      var $modal = $(this).closest(".event-registration-modal-overlay");
      closeRegistrationModal($modal);
    });

    // Cerrar modal al hacer clic en el overlay
    $(document).on("click", ".event-registration-modal-overlay", function (e) {
      if ($(e.target).is(".event-registration-modal-overlay")) {
        closeRegistrationModal($(this));
      }
    });

    // Cerrar modal con tecla Escape
    $(document).on("keydown", function (e) {
      if (e.key === "Escape") {
        var $openModal = $(".event-registration-modal-overlay:visible");
        if ($openModal.length) {
          closeRegistrationModal($openModal);
        }
      }
    });

    // Función para cerrar el modal
    function closeRegistrationModal($modal) {
      $modal.fadeOut(200, function () {
        $("body").removeClass("modal-open");
      });
    }

    // Manejar envío del formulario de registro de asistentes
    $(document).on("submit", ".event-registration-form", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $form.find(".event-register-btn");
      var $messages = $form.find(".form-messages");

      // Deshabilitar botón y mostrar loading
      $btn.prop("disabled", true);
      $btn.find(".btn-text").hide();
      $btn.find(".btn-loading").show();

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: $form.serialize(),
        success: function (response) {
          if (response.success) {
            $messages
              .removeClass("error")
              .addClass("success")
              .html(response.data.message)
              .show();
            $form[0].reset();

            // Cerrar modal después de éxito (opcional, con delay)
            setTimeout(function () {
              var $modal = $form.closest(".event-registration-modal-overlay");
              if ($modal.length) {
                closeRegistrationModal($modal);
              }
            }, 2500);
          } else {
            $messages
              .removeClass("success")
              .addClass("error")
              .html(response.data.message)
              .show();
          }
        },
        error: function () {
          $messages
            .removeClass("success")
            .addClass("error")
            .html(
              eventShowData.i18n && eventShowData.i18n.error
                ? eventShowData.i18n.error
                : "Error al procesar la solicitud",
            )
            .show();
        },
        complete: function () {
          $btn.prop("disabled", false);
          $btn.find(".btn-text").show();
          $btn.find(".btn-loading").hide();
        },
      });
    });
  }

  /**
   * Inicializar formulario de envío de eventos
   */
  function initSubmitEventForm() {
    // Cuando se selecciona un establecimiento organizador, cargar su lugar
    $("#event_organizer_id").on("change", function () {
      var establecimientoId = $(this).val();

      if (establecimientoId) {
        // Mostrar la opción de usar el establecimiento como lugar
        $("#use-organizer-location-option").show();

        // Consultar datos del establecimiento
        $.ajax({
          url: eventShowData.ajaxUrl,
          type: "POST",
          data: {
            action: "event_show_get_establecimiento_data",
            establecimiento_id: establecimientoId,
            nonce: eventShowData.nonce,
          },
          success: function (response) {
            if (response.success) {
              console.log(
                "Establecimiento cargado - ID:",
                establecimientoId,
                "Lugar ID:",
                response.data.lugar_id,
              );

              // Si el establecimiento tiene un lugar, pre-seleccionarlo
              if (response.data.lugar_id) {
                $("#event_location").val(response.data.lugar_id);
              }
            }
          },
          error: function () {
            console.error("Error al cargar datos del establecimiento");
          },
        });
      } else {
        // Ocultar la opción si no hay establecimiento seleccionado
        $("#use-organizer-location-option").hide();
        if ($("#event_location").val() === "USE_ORGANIZER_AS_LOCATION") {
          $("#event_location").val("");
        }
      }
    });
  }

  /**
   * Inicializar carrusel
   */
  function initCarousel() {
    $(".event-show-carousel").each(function () {
      var $carousel = $(this);
      var $track = $carousel.find(".carousel-track");
      var $slides = $carousel.find(".carousel-slide");
      var currentIndex = 0;

      if ($slides.length <= 1) {
        $carousel.find(".carousel-control, .carousel-indicators").hide();
        return;
      }

      // Navegación
      $carousel.find(".carousel-next").on("click", function () {
        currentIndex = (currentIndex + 1) % $slides.length;
        updateCarousel();
      });

      $carousel.find(".carousel-prev").on("click", function () {
        currentIndex = (currentIndex - 1 + $slides.length) % $slides.length;
        updateCarousel();
      });

      // Indicadores
      $carousel.find(".carousel-indicator").on("click", function () {
        currentIndex = $(this).data("slide");
        updateCarousel();
      });

      // Auto-play
      var autoplay = setInterval(function () {
        currentIndex = (currentIndex + 1) % $slides.length;
        updateCarousel();
      }, 3000);

      $carousel.on("mouseenter", function () {
        clearInterval(autoplay);
      });

      function updateCarousel() {
        $track.css("transform", "translateX(" + -currentIndex * 100 + "%)");
        $carousel
          .find(".carousel-indicator")
          .removeClass("active")
          .eq(currentIndex)
          .addClass("active");
      }
    });
  }

  /**
   * Inicializar tabs del dashboard
   */
  function initDashboardTabs() {
    $(".dashboard-tab").on("click", function () {
      var tabId = $(this).data("tab");

      $(".dashboard-tab").removeClass("active");
      $(this).addClass("active");

      $(".dashboard-tab-content").removeClass("active");
      $("#tab-" + tabId).addClass("active");
    });
  }

  /**
   * Inicializar botones compartir
   */
  function initShareButtons() {
    $(".event-share-btn").on("click", function (e) {
      e.preventDefault();

      var url = window.location.href;
      var title = document.title;

      if (navigator.share) {
        navigator.share({
          title: title,
          url: url,
        });
      } else {
        // Copiar al portapapeles
        var tempInput = $("<input>");
        $("body").append(tempInput);
        tempInput.val(url).select();
        document.execCommand("copy");
        tempInput.remove();
        alert("¡Enlace copiado al portapapeles!");
      }
    });
  }

  /**
   * Inicializar datepicker
   */
  function initDatepicker() {
    if (typeof $.fn.datepicker !== "undefined") {
      // Obtener días mínimos de anticipación desde la configuración
      var minDaysAdvance =
        eventShowData && eventShowData.minDaysAdvance
          ? parseInt(eventShowData.minDaysAdvance)
          : 5;

      // Calcular la fecha mínima permitida
      var minDate = new Date();
      minDate.setDate(minDate.getDate() + minDaysAdvance);

      $(".event-datepicker").datepicker({
        dateFormat: "dd/mm/yy",
        minDate: minDate,
        changeMonth: true,
        changeYear: true,
        yearRange: "c:c+5",
        beforeShow: function (input, inst) {
          // Asegurar que el calendario se muestre correctamente
          setTimeout(function () {
            inst.dpDiv.css({
              "z-index": 9999,
            });
          }, 0);
        },
        onSelect: function (dateText, inst) {
          // Trigger change event para validaciones
          $(this).trigger("change");
        },
      });
    }
  }

  /**
   * Inicializar datetime picker para campos combinados
   */
  function initDatetimePicker() {
    // Obtener días mínimos de anticipación
    var minDaysAdvance =
      eventShowData && eventShowData.minDaysAdvance
        ? parseInt(eventShowData.minDaysAdvance)
        : 5;

    // Calcular fecha/hora mínima
    var minDateTime = new Date();
    minDateTime.setDate(minDateTime.getDate() + minDaysAdvance);

    // Formatear para datetime-local (YYYY-MM-DDTHH:MM)
    var minDateTimeStr =
      minDateTime.getFullYear() +
      "-" +
      String(minDateTime.getMonth() + 1).padStart(2, "0") +
      "-" +
      String(minDateTime.getDate()).padStart(2, "0") +
      "T00:00";

    // Aplicar al campo datetime-local
    $(".event-datetime-picker").attr("min", minDateTimeStr);
  }

  /**
   * Inicializar modales
   */
  function initModals() {
    // Abrir modal de crear evento
    $(document).on("click", "#open-create-event-modal", function (e) {
      e.preventDefault();
      $("#create-event-modal").fadeIn();
    });
    // Abrir modal de organizador
    $(document).on("click", ".create-organizer-link", function (e) {
      e.preventDefault();
      $("#create-organizer-modal").fadeIn();
    });

    // Abrir modal de lugar (flotante)
    $(document).on("click", ".create-location-link", function (e) {
      e.preventDefault();
      $("#create-location-modal").fadeIn();
    });

    // Cerrar modal (soporta .event-modal y .event-modal-overlay)
    $(document).on("click", ".modal-close", function () {
      $(this).closest(".event-modal, .event-modal-overlay").fadeOut();
    });

    $(window).on("click", function (e) {
      if (
        $(e.target).hasClass("event-modal") ||
        $(e.target).hasClass("event-modal-overlay")
      ) {
        $(".event-modal, .event-modal-overlay").fadeOut();
      }
    });

    // Formulario crear organizador
    $("#create-organizer-form").on("submit", function (e) {
      e.preventDefault();

      var formData =
        $(this).serialize() +
        "&action=event_show_create_organizer&nonce=" +
        eventShowData.nonce;

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            // Añadir al select
            $("#event_organizer")
              .append(
                $("<option>", {
                  value: response.data.term_id,
                  text: response.data.term_name,
                }),
              )
              .val(response.data.term_id);

            $("#create-organizer-modal").fadeOut();
            $("#create-organizer-form")[0].reset();
            alert(response.data.message);
          } else {
            alert(response.data.message);
          }
        },
      });
    });

    // Vista previa de imagen lugar
    $(document).on("change", "#create_loc_image_file", function (e) {
      var input = this;
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $("#create_loc_image_preview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
      } else {
        $("#create_loc_image_preview").hide();
      }
    });

    // Formulario crear lugar (con imagen)
    $(document).on("submit", "#create-location-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var formData = new FormData($form[0]);
      formData.append("action", "event_show_create_location");
      formData.append("nonce", eventShowData.nonce);
      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#create-location-modal").fadeOut();
            $("#create-location-form")[0].reset();
            $("#create_loc_image_preview").hide();
            alert(response.data.message);
            window.location.reload();
          } else {
            alert(response.data.message);
          }
        },
      });
    });

    // Guardar edición de organizador
    $(document).on("submit", "#edit-organizer-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var formData = new FormData($form[0]);
      formData.append("action", "event_show_edit_organizer");
      formData.append("nonce", eventShowData.nonce);
      var $btn = $form.find("button[type='submit']");
      $btn.prop("disabled", true);
      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            $("#edit-organizer-modal").fadeOut();
            window.location.reload();
          } else {
            alert(response.data.message);
          }
        },
        complete: function () {
          $btn.prop("disabled", false);
        },
      });
    });

    // Guardar edición de evento
    $(document).on("submit", "#edit-event-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find("button[type='submit']");
      var $messages = $form.find(".form-messages");

      // Convertir datetime-local a formatos separados
      var datetimeStart = $("#edit_event_datetime_start").val();

      if (!datetimeStart) {
        $messages
          .removeClass("success")
          .addClass("error")
          .html(
            "⚠️ Por favor, selecciona la fecha y hora de inicio del evento.",
          )
          .show();
        $("html, body").animate(
          { scrollTop: $messages.offset().top - 100 },
          500,
        );
        return false;
      }

      if (datetimeStart) {
        var dtStart = new Date(datetimeStart);

        // Verificar que la fecha sea válida
        if (isNaN(dtStart.getTime())) {
          $messages
            .removeClass("success")
            .addClass("error")
            .html(
              "⚠️ La fecha de inicio no es válida. Por favor, selecciona una fecha correcta.",
            )
            .show();
          $("html, body").animate(
            { scrollTop: $messages.offset().top - 100 },
            500,
          );
          return false;
        }

        var dateStr =
          String(dtStart.getDate()).padStart(2, "0") +
          "/" +
          String(dtStart.getMonth() + 1).padStart(2, "0") +
          "/" +
          dtStart.getFullYear();
        var timeStr =
          String(dtStart.getHours()).padStart(2, "0") +
          ":" +
          String(dtStart.getMinutes()).padStart(2, "0");

        $("#edit_event_date").val(dateStr);
        $("#edit_event_time").val(timeStr);
      }

      var datetimeEnd = $("#edit_event_datetime_end").val();
      if (datetimeEnd) {
        var dtEnd = new Date(datetimeEnd);

        // Verificar que la fecha sea válida
        if (!isNaN(dtEnd.getTime())) {
          var dateEndStr =
            String(dtEnd.getDate()).padStart(2, "0") +
            "/" +
            String(dtEnd.getMonth() + 1).padStart(2, "0") +
            "/" +
            dtEnd.getFullYear();
          var timeEndStr =
            String(dtEnd.getHours()).padStart(2, "0") +
            ":" +
            String(dtEnd.getMinutes()).padStart(2, "0");
          $("#edit_event_end_date").val(dateEndStr);
          $("#edit_event_end_time").val(timeEndStr);
        }
      }

      // Validar fecha de evento
      var eventDate = $("#edit_event_date").val();
      if (eventDate) {
        var minDaysAdvance =
          eventShowData && eventShowData.minDaysAdvance
            ? parseInt(eventShowData.minDaysAdvance)
            : 5;

        // Parsear fecha (formato dd/mm/yyyy)
        var parts = eventDate.split("/");
        if (parts.length === 3) {
          var selectedDate = new Date(parts[2], parts[1] - 1, parts[0]);
          var today = new Date();
          today.setHours(0, 0, 0, 0);

          var minDate = new Date();
          minDate.setDate(minDate.getDate() + minDaysAdvance);
          minDate.setHours(0, 0, 0, 0);

          if (selectedDate < minDate) {
            var minDateStr =
              String(minDate.getDate()).padStart(2, "0") +
              "/" +
              String(minDate.getMonth() + 1).padStart(2, "0") +
              "/" +
              minDate.getFullYear();

            $messages
              .removeClass("success")
              .addClass("error")
              .html(
                "⚠️ La fecha del evento debe ser al menos " +
                  minDaysAdvance +
                  " día" +
                  (minDaysAdvance > 1 ? "s" : "") +
                  " a partir de hoy. Fecha mínima permitida: " +
                  minDateStr +
                  ".",
              )
              .show();
            $("html, body").animate(
              {
                scrollTop: $messages.offset().top - 100,
              },
              500,
            );
            return false;
          }
        }
      }

      $btn.prop("disabled", true).text("Guardando...");

      var formData = new FormData(this);
      formData.append("action", "event_show_edit_event");
      formData.append("nonce", eventShowData.nonce);

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $messages
              .removeClass("error")
              .addClass("success")
              .html(response.data.message)
              .show();
            setTimeout(function () {
              $("#edit-event-modal").fadeOut();
              window.location.reload();
            }, 1500);
          } else {
            $messages
              .removeClass("success")
              .addClass("error")
              .html(response.data.message)
              .show();
          }
        },
        error: function () {
          $messages
            .removeClass("success")
            .addClass("error")
            .html("Error al guardar")
            .show();
        },
        complete: function () {
          $btn.prop("disabled", false).text("Guardar Cambios");
        },
      });
    });
  }

  /**
   * Inicializar countdown
   */
  function initCountdown() {
    $(".slide-countdown").each(function () {
      var $countdown = $(this);
      var totalSeconds = parseInt($countdown.data("countdown"));
      if (!totalSeconds || totalSeconds <= 0) return;

      // Selección de elementos
      var $days = $countdown.find(".clock-day");
      var $hours = $countdown.find(".clock-hours");
      var $minutes = $countdown.find(".clock-minutes");
      var $seconds = $countdown.find(".clock-seconds");

      function updateTimer() {
        totalSeconds--;
        if (totalSeconds < 0) {
          $days.text("D");
          $hours.text("O");
          $minutes.text("N");
          $seconds.text("E");
          return;
        }
        var days = Math.floor(totalSeconds / (60 * 60 * 24));
        var hours = Math.floor((totalSeconds % (60 * 60 * 24)) / (60 * 60));
        var minutes = Math.floor((totalSeconds % (60 * 60)) / 60);
        var seconds = totalSeconds % 60;

        // Formatear con ceros
        var formattedSeconds = seconds < 10 ? "0" + seconds : seconds;
        var formattedMinutes = minutes < 10 ? "0" + minutes : minutes;
        var formattedHours = hours < 10 ? "0" + hours : hours;

        function animate($el, value) {
          if ($el.text() != value) {
            $el.addClass("clock-updating");
            setTimeout(function () {
              $el.text(value).removeClass("clock-updating");
            }, 150);
          } else {
            $el.text(value);
          }
        }
        animate($days, days);
        animate($hours, formattedHours);
        animate($minutes, formattedMinutes);
        animate($seconds, formattedSeconds);
      }
      updateTimer();
      setInterval(updateTimer, 1000);
    });
  }

  /**
   * Inicializar slider de eventos
   */
  function initSlider() {
    $(".event-show-slider").each(function () {
      var $slider = $(this);
      var $images = $slider.find(".slider-image-item");
      var $infos = $slider.find(".slider-info-item");
      var $indicators = $slider.find(".slider-indicator");
      var currentIndex = 0;
      var totalSlides = $images.length;
      var autoplay = $slider.data("autoplay") === 1;
      var autoplaySpeed = $slider.data("autoplay-speed") || 5000;
      var autoplayInterval;

      if (totalSlides <= 1) {
        $slider.find(".slider-controls, .slider-indicators").hide();
        return;
      }

      // Actualizar posiciones de las imágenes
      function updateSlider() {
        $images.each(function (index) {
          var $img = $(this);
          var diff = index - currentIndex;

          // Limpiar clases
          $img.removeClass("active next far-next prev far-prev");

          // Calcular posición relativa considerando loop
          if (diff === 0) {
            $img.addClass("active");
          } else if (diff === 1 || diff === -(totalSlides - 1)) {
            $img.addClass("next");
          } else if (diff === 2 || diff === -(totalSlides - 2)) {
            $img.addClass("far-next");
          } else if (diff === -1 || diff === totalSlides - 1) {
            $img.addClass("prev");
          } else if (diff === -2 || diff === totalSlides - 2) {
            $img.addClass("far-prev");
          }
        });

        // Actualizar panel de información
        $infos.removeClass("active").eq(currentIndex).addClass("active");
      }

      // Siguiente slide
      function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateSlider();
      }

      // Anterior slide
      function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateSlider();
      }

      // Ir a slide específico
      function goToSlide(index) {
        currentIndex = index;
        updateSlider();
      }

      // Controles
      $slider.find(".slider-next").on("click", function () {
        nextSlide();
        resetAutoplay();
      });

      $slider.find(".slider-prev").on("click", function () {
        prevSlide();
        resetAutoplay();
      });

      // Click en imagen para ir a ese slide
      $images.on("click", function (e) {
        var $img = $(this);
        if (!$img.hasClass("active")) {
          e.preventDefault();
          var index = $img.data("slide-index");
          goToSlide(index);
          resetAutoplay();
        }
      });

      // Autoplay
      function startAutoplay() {
        if (autoplay) {
          autoplayInterval = setInterval(nextSlide, autoplaySpeed);
        }
      }

      function stopAutoplay() {
        if (autoplayInterval) {
          clearInterval(autoplayInterval);
        }
      }

      function resetAutoplay() {
        stopAutoplay();
        startAutoplay();
      }

      // Pausar en hover
      $slider.on("mouseenter", function () {
        stopAutoplay();
      });

      $slider.on("mouseleave", function () {
        startAutoplay();
      });

      // Soporte para teclado
      $slider.attr("tabindex", "0");
      $slider.on("keydown", function (e) {
        if (e.key === "ArrowRight") {
          nextSlide();
          resetAutoplay();
        } else if (e.key === "ArrowLeft") {
          prevSlide();
          resetAutoplay();
        }
      });

      // Soporte para swipe en móviles
      var touchStartX = 0;
      var touchEndX = 0;

      $slider.on("touchstart", function (e) {
        touchStartX = e.originalEvent.touches[0].clientX;
      });

      $slider.on("touchend", function (e) {
        touchEndX = e.originalEvent.changedTouches[0].clientX;
        handleSwipe();
      });

      function handleSwipe() {
        var swipeThreshold = 50;
        var diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
          if (diff > 0) {
            nextSlide();
          } else {
            prevSlide();
          }
          resetAutoplay();
        }
      }

      // Iniciar
      updateSlider();
      startAutoplay();
    });
  }

  /**
   * Ver asistentes en modal
   */
  var currentEventIdForExport = null;

  $(document).on("click", ".view-attendees-link", function (e) {
    e.preventDefault();

    var eventId = $(this).data("event-id");
    var eventTitle = $(this).data("event-title");

    currentEventIdForExport = eventId;

    // Mostrar modal
    $("#attendees-modal").fadeIn();
    $("#attendees-event-title").text(eventTitle);
    $("#attendees-list-container").html("<p>Cargando...</p>");

    // Obtener asistentes
    $.ajax({
      url: eventShowData.ajaxUrl,
      type: "POST",
      data: {
        action: "event_show_get_attendees",
        event_id: eventId,
        nonce: eventShowData.nonce,
      },
      success: function (response) {
        if (response.success) {
          var attendees = response.data.attendees;
          if (attendees && attendees.length > 0) {
            var html = '<table class="dashboard-table">';
            html += "<thead><tr>";
            html += "<th>Nombre</th>";
            html += "<th>Email</th>";
            html += "<th>Teléfono</th>";
            html += "<th>Cantidad</th>";
            html += "<th>Fecha de Registro</th>";
            html += "</tr></thead>";
            html += "<tbody>";

            attendees.forEach(function (attendee) {
              html += "<tr>";
              html += "<td>" + attendee.name + "</td>";
              html += "<td>" + attendee.email + "</td>";
              html += "<td>" + (attendee.phone || "-") + "</td>";
              html += "<td>" + attendee.num_attendees + "</td>";
              html += "<td>" + attendee.registration_date + "</td>";
              html += "</tr>";
            });

            html += "</tbody></table>";
            $("#attendees-list-container").html(html);
          } else {
            $("#attendees-list-container").html(
              "<p>No hay asistentes registrados.</p>",
            );
          }
        } else {
          $("#attendees-list-container").html(
            "<p>Error al cargar asistentes.</p>",
          );
        }
      },
      error: function () {
        $("#attendees-list-container").html(
          "<p>Error al cargar asistentes.</p>",
        );
      },
    });
  });

  /**
   * Exportar asistentes desde modal
   */
  $(document).on("click", "#export-attendees-csv", function (e) {
    e.preventDefault();

    if (!currentEventIdForExport) {
      alert("Error: No se pudo determinar el evento");
      return;
    }

    var url =
      eventShowData.ajaxUrl +
      "?action=event_show_export_attendees&event_id=" +
      currentEventIdForExport +
      "&nonce=" +
      eventShowData.nonce;
    window.location.href = url;
  });

  /**
   * Paginación para layouts Grid y List
   */
  function initEventPagination() {
    // Load More button
    $(document).on("click", ".event-load-more-btn", function (e) {
      e.preventDefault();
      var $btn = $(this);
      var $container = $btn.closest(".event-show-grid, .event-show-list");
      var nextPage = parseInt($btn.data("next-page"));
      var atts = $container.data("atts");

      // Asegurarse de que atts sea un objeto
      if (typeof atts === "string") {
        try {
          atts = JSON.parse(atts);
        } catch (e) {
          atts = {};
        }
      }

      loadMoreEvents(
        $container,
        nextPage,
        atts,
        function (html) {
          var $eventsContainer = $container.find(
            ".events-grid-container, .events-list-container",
          );
          $eventsContainer.append(html);

          var maxPages = parseInt($container.data("max-pages"));
          if (nextPage >= maxPages) {
            $btn.parent().remove();
          } else {
            $btn.data("next-page", nextPage + 1);
            $container.attr("data-paged", nextPage);
          }
        },
        $btn,
      );
    });

    // Infinite scroll
    $(".event-show-grid, .event-show-list").each(function () {
      var $container = $(this);
      var paginationType = $container.data("pagination-type");

      if (paginationType === "infinite") {
        var isLoading = false;
        var $trigger = $container.find(".event-show-infinite-trigger");
        var $loading = $container.find(".event-show-infinite-loading");
        var atts = $container.data("atts");

        // Asegurarse de que atts sea un objeto
        if (typeof atts === "string") {
          try {
            atts = JSON.parse(atts);
          } catch (e) {
            atts = {};
          }
        }

        if ($trigger.length) {
          var observer = new IntersectionObserver(
            function (entries) {
              entries.forEach(function (entry) {
                if (entry.isIntersecting && !isLoading) {
                  isLoading = true;
                  $loading.show();

                  var nextPage = parseInt($trigger.data("next-page"));

                  loadMoreEvents($container, nextPage, atts, function (html) {
                    var $eventsContainer = $container.find(
                      ".events-grid-container, .events-list-container",
                    );
                    $eventsContainer.append(html);

                    var maxPages = parseInt($container.data("max-pages"));
                    if (nextPage >= maxPages) {
                      $trigger.remove();
                      $loading.remove();
                      observer.disconnect();
                    } else {
                      $trigger.data("next-page", nextPage + 1);
                      $container.attr("data-paged", nextPage);
                      isLoading = false;
                      $loading.hide();
                    }
                  });
                }
              });
            },
            { threshold: 0.1 },
          );

          observer.observe($trigger[0]);
        }
      }
    });
  }

  /**
   * Cargar más eventos via AJAX
   */
  function loadMoreEvents($container, page, atts, callback, $btn) {
    var layout = $container.data("layout");

    if ($btn) {
      $btn.prop("disabled", true);
      $btn.find(".btn-text").hide();
      $btn.find(".btn-loading").show();
    }

    $.ajax({
      url: eventShowData.ajaxUrl,
      type: "POST",
      data: {
        action: "event_show_load_more",
        page: page,
        layout: layout,
        atts: JSON.stringify(atts),
        nonce: eventShowData.nonce,
      },
      success: function (response) {
        if (response.success) {
          callback(response.data.html);
        }

        if ($btn) {
          $btn.prop("disabled", false);
          $btn.find(".btn-text").show();
          $btn.find(".btn-loading").hide();
        }
      },
      error: function () {
        alert("Error al cargar más eventos");

        if ($btn) {
          $btn.prop("disabled", false);
          $btn.find(".btn-text").show();
          $btn.find(".btn-loading").hide();
        }
      },
    });
  }
})(jQuery);
