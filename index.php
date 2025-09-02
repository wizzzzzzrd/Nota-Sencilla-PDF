<?php
// index.php
// Formulario bootstrap para rellenar la "Nota"
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rellenar Nota — Rosenberg</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-light">
  <div class="container py-4 mt-4 mt-md-5">
    <div class="mb-3">
      <h2 class="d-flex align-items-center gap-2 mb-0">
        <!-- altura fija con style inline (solo Bootstrap - sin CSS separado) -->
        <img src="templates/Images/Rosenberg LOGO.png"
          alt="Logotipo Rosenberg"
          class="img-fluid"
          style="height:3rem; width:auto;"
          loading="lazy">
        <span class="fs-3 fw-normal ms-2">NOTA</span>
      </h2>
    </div>
    <form id="notaForm" method="post" action="controller/process.php" target="_blank">
      <div class="card mb-3">
        <div class="card-body">
          <div class="row g-3">

            <!-- Nuevo campo: Nombre de Vendedor -->
            <div class="col-md-6">
              <label class="form-label">Nombre de Vendedor</label>
              <input class="form-control" name="vendedor_nombre" id="vendedor_nombre" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Nombre (Cliente)</label>
              <input class="form-control" name="cliente_nombre" id="cliente_nombre" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Fecha</label>
              <input class="form-control" name="fecha" id="fecha" type="date" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nota No.</label>
              <input class="form-control" name="nota_no" id="nota_no">
            </div>

            <div class="col-12">
              <label class="form-label">Domicilio</label>
              <input class="form-control" name="domicilio" id="domicilio">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h5>Articulos</h5>
          <div class="table-responsive">
            <table class="table table-bordered table-fixed" id="itemsTable">
              <thead class="table-light">
                <tr>
                  <th class="col-qty">Cantidad</th>
                  <th class="col-desc">Descripción</th>
                  <th class="col-unit">Valor unitario</th>
                  <th class="col-imp">Importe</th>
                  <th style="width:40px"></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><input name="qty[]" class="form-control qty" type="number" min="0" step="1" value="1"></td>
                  <td><input name="desc[]" class="form-control desc" type="text"></td>
                  <td><input name="unit[]" class="form-control unit" type="number" min="0" step="0.01" value="0.00"></td>
                  <td class="importe align-middle">0.00</td>
                  <td class="text-center align-middle"><button class="btn btn-sm btn-danger remove-row" type="button">—</button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2">
            <div>
              <button id="addRow" class="btn btn-sm btn-primary" type="button">+ Agregar fila</button>
            </div>
            <div class="w-25">
              <label class="form-label">Total</label>
              <input readonly id="total" name="total" class="form-control" value="0.00">
            </div>
          </div>
        </div>
      </div>

      <!-- hidden input que envía el JSON de items -->
      <input type="hidden" name="items_json" id="items_json">

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success" id="sendBtn">Enviar y generar PDF</button>
        <button type="reset" class="btn btn-secondary">Limpiar</button>
      </div>
    </form>
  </div>

  <script>
    (function() {
      const itemsTable = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
      const addRowBtn = document.getElementById('addRow');
      const totalInput = document.getElementById('total');
      const form = document.getElementById('notaForm');
      const itemsJsonInput = document.getElementById('items_json');

      function recalcRow(tr) {
        const qty = parseFloat(tr.querySelector('.qty').value) || 0;
        const unit = parseFloat(tr.querySelector('.unit').value) || 0;
        const imp = (qty * unit);
        tr.querySelector('.importe').innerText = imp.toFixed(2);
        recalcTotal();
      }

      function recalcTotal() {
        let sum = 0;
        itemsTable.querySelectorAll('tr').forEach(tr => {
          sum += parseFloat(tr.querySelector('.importe').innerText) || 0;
        });
        totalInput.value = sum.toFixed(2);
      }

      function attachRowEvents(tr) {
        tr.querySelectorAll('.qty, .unit').forEach(inp => {
          inp.addEventListener('input', () => recalcRow(tr));
        });
        tr.querySelector('.remove-row').addEventListener('click', () => {
          if (itemsTable.rows.length > 1) {
            tr.remove();
            recalcTotal();
          } else {
            tr.querySelector('.qty').value = 0;
            tr.querySelector('.unit').value = 0;
            tr.querySelector('.desc').value = '';
            recalcRow(tr);
          }
        });
      }

      attachRowEvents(itemsTable.querySelector('tr'));

      addRowBtn.addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td><input name="qty[]" class="form-control qty" type="number" min="0" step="1" value="1"></td>
      <td><input name="desc[]" class="form-control desc" type="text"></td>
      <td><input name="unit[]" class="form-control unit" type="number" min="0" step="0.01" value="0.00"></td>
      <td class="importe align-middle">0.00</td>
      <td class="text-center align-middle"><button class="btn btn-sm btn-danger remove-row" type="button">—</button></td>
    `;
        itemsTable.appendChild(tr);
        attachRowEvents(tr);
      });

      // before submit, build JSON of items and put into hidden input
      form.addEventListener('submit', function(e) {
        const items = [];
        itemsTable.querySelectorAll('tr').forEach(tr => {
          const qty = parseFloat(tr.querySelector('.qty').value) || 0;
          const desc = tr.querySelector('.desc').value || '';
          const unit = parseFloat(tr.querySelector('.unit').value) || 0;
          const imp = parseFloat((qty * unit).toFixed(2)) || 0;
          items.push({
            qty,
            desc,
            unit,
            imp
          });
        });
        itemsJsonInput.value = JSON.stringify(items);
        // allow default submit (target _blank) — PDF abrirá en nueva pestaña
      });

    })();
  </script>
</body>

</html>