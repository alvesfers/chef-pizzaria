<?php
// cupons.php
require_once 'assets/header.php';

// só administradores podem acessar
if (!($isAdmin ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<div class="container mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Cupons de Desconto</h1>
    <button id="btnNovoCupom" class="btn btn-primary">Novo Cupom</button>
  </div>

  <table id="tableCupons" class="table w-full">
    <thead>
      <tr>
        <th>ID</th>
        <th>Código</th>
        <th>Tipo</th>
        <th>Valor</th>
        <th>Uso Único</th>
        <th>Usos Restantes</th>
        <th>Mínimo Pedido</th>
        <th>Válido De</th>
        <th>Válido Até</th>
        <th>Ativo</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal de Cadastro/Edição -->
<input type="checkbox" id="modal-cupom" class="modal-toggle" />
<div class="modal">
  <div class="modal-box max-w-lg">
    <h3 id="titulo-modal" class="font-bold text-lg mb-4">Novo Cupom</h3>
    <form id="formCupom" class="space-y-4">
      <input type="hidden" id="id_cupom" name="id_cupom">

      <div>
        <label class="block font-medium">Código</label>
        <input type="text" id="codigo" name="codigo" class="input input-bordered w-full" required>
      </div>
      <div>
        <label class="block font-medium">Descrição</label>
        <input type="text" id="descricao" name="descricao" class="input input-bordered w-full">
      </div>
      <div>
        <label class="block font-medium">Tipo</label>
        <select id="tipo" name="tipo" class="select select-bordered w-full" required>
          <option value="fixo">Fixo (R$)</option>
          <option value="percentual">Percentual (%)</option>
        </select>
      </div>
      <div>
        <label class="block font-medium">Valor</label>
        <input type="text" id="valor" name="valor" class="input input-bordered w-full" required>
      </div>
      <div class="flex items-center space-x-2">
        <input type="checkbox" id="uso_unico" name="uso_unico" class="checkbox" />
        <label for="uso_unico">Uso único</label>
      </div>
      <div>
        <label class="block font-medium">Quantidade de usos (vazio = ilimitado)</label>
        <input type="number" id="quantidade_usos" name="quantidade_usos" class="input input-bordered w-full" min="1">
      </div>
      <div>
        <label class="block font-medium">Mínimo de Pedido (R$)</label>
        <input type="text" id="minimo_pedido" name="minimo_pedido" class="input input-bordered w-full" value="0">
      </div>
      <div>
        <label class="block font-medium">Válido de</label>
        <input type="date" id="valido_de" name="valido_de" class="input input-bordered w-full" required>
      </div>
      <div>
        <label class="block font-medium">Válido até</label>
        <input type="date" id="valido_ate" name="valido_ate" class="input input-bordered w-full">
      </div>
      <div class="flex items-center space-x-2">
        <input type="checkbox" id="cupom_ativo" name="cupom_ativo" class="checkbox" checked />
        <label for="cupom_ativo">Cupom ativo</label>
      </div>

      <div class="modal-action">
        <label for="modal-cupom" class="btn">Cancelar</label>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'assets/footer.php'; ?>

<script>
$(function(){
  // mascara para valores
  $('#valor, #minimo_pedido').mask('#.##0,00',{reverse:true});

  function carregarCupons() {
    $.post('crud/crud_cupom.php', { action: 'listar' }, res => {
      if (res.status !== 'ok') return Swal.fire('Erro', 'Não foi possível carregar.', 'error');
      let html = '';
      res.cupons.forEach(c => {
        html += `
          <tr>
            <td>${c.id_cupom}</td>
            <td>${c.codigo}</td>
            <td>${c.tipo}</td>
            <td>${parseFloat(c.valor).toFixed(2).replace('.',',')}</td>
            <td>${c.uso_unico? 'Sim':'Não'}</td>
            <td>${c.quantidade_usos ?? '∞'}</td>
            <td>${parseFloat(c.minimo_pedido).toFixed(2).replace('.',',')}</td>
            <td>${c.valido_de}</td>
            <td>${c.valido_ate ?? '-'}</td>
            <td>${c.cupom_ativo? 'Sim':'Não'}</td>
            <td class="space-x-2">
              <button class="btn btn-sm btn-info btn-edit" data-id="${c.id_cupom}">Editar</button>
              <button class="btn btn-sm btn-error btn-delete" data-id="${c.id_cupom}">Desativar</button>
            </td>
          </tr>`;
      });
      $('#tableCupons tbody').html(html);
    }, 'json');
  }

  // abrir modal novo
  $('#btnNovoCupom').click(()=>{
    $('#titulo-modal').text('Novo Cupom');
    $('#formCupom')[0].reset();
    $('#id_cupom').val('');
    $('#cupom_ativo').prop('checked', true);
    $('#modal-cupom').prop('checked', true);
  });

  // enviar formulário (novo ou edição)
  $('#formCupom').submit(function(e){
    e.preventDefault();
    const id = $('#id_cupom').val();
    const act = id ? 'atualizar' : 'cadastrar';
    let dados = $(this).serializeArray();
    dados.push({ name:'action', value: act });
    $.post('crud/crud_cupom.php', dados, res=>{
      if (res.status==='ok') {
        Swal.fire('Sucesso', res.mensagem, 'success');
        $('#modal-cupom').prop('checked', false);
        carregarCupons();
      } else {
        Swal.fire('Erro', res.mensagem, 'error');
      }
    }, 'json');
  });

  // editar
  $('#tableCupons').on('click','.btn-edit', function(){
    const id = $(this).data('id');
    $.post('crud/crud_cupom.php',{ action:'obter', id_cupom:id }, res=>{
      if (res.status!=='ok') return Swal.fire('Erro', res.mensagem,'error');
      const c = res.cupom;
      $('#titulo-modal').text('Editar Cupom');
      $('#id_cupom').val(c.id_cupom);
      $('#codigo').val(c.codigo);
      $('#descricao').val(c.descricao);
      $('#tipo').val(c.tipo);
      $('#valor').val(parseFloat(c.valor).toFixed(2).replace('.',','));
      $('#uso_unico').prop('checked',!!c.uso_unico);
      $('#quantidade_usos').val(c.quantidade_usos);
      $('#minimo_pedido').val(parseFloat(c.minimo_pedido).toFixed(2).replace('.',','));
      $('#valido_de').val(c.valido_de);
      $('#valido_ate').val(c.valido_ate);
      $('#cupom_ativo').prop('checked',!!c.cupom_ativo);
      $('#modal-cupom').prop('checked', true);
    }, 'json');
  });

  // desativar
  $('#tableCupons').on('click','.btn-delete', function(){
    const id = $(this).data('id');
    Swal.fire({
      title: 'Desativar cupom?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, desativar'
    }).then(result => {
      if (result.isConfirmed) {
        $.post('crud/crud_cupom.php',{ action:'deletar', id_cupom:id }, res=>{
          if (res.status==='ok') {
            Swal.fire('Desativado', res.mensagem,'success');
            carregarCupons();
          } else {
            Swal.fire('Erro', res.mensagem,'error');
          }
        },'json');
      }
    });
  });

  // inicializa
  carregarCupons();
});
</script>
