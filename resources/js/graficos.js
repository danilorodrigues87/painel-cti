console.log('cheguei')


// FUNÇÃO QUE CARREGA DADOS DA HOME
Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#292b2c';
  
  $.ajax({
   type: 'POST',
   url: url_base+carregaDadosHome,
   dataType: "json",
   success: function(result) {

    graficoVendas(result)
    graficoFinanca(result)
    graficoTopVendas(result)
      
  }
})

 function graficoVendas(result) {

  var ctx_vendas = document.getElementById("graficoVendas");

  var myLineChart_vendas = new Chart(ctx_vendas, {
    type: 'line',
    data: {
      labels: result.vendas_meses,
      datasets: [{
        label: "matricula(s)",
        lineTension: 0.3,
        backgroundColor: "rgba(2,117,216,0.2)",
        borderColor: "rgba(2,117,216,1)",
        pointRadius: 5,
        pointBackgroundColor: "rgba(2,117,216,1)",
        pointBorderColor: "rgba(255,255,255,0.8)",
        pointHoverRadius: 5,
        pointHoverBackgroundColor: "rgba(2,117,216,1)",
        pointHitRadius: 50,
        pointBorderWidth: 2,
        data: result.vendas_valores,
      }],
    },
    options: {
      scales: {
        xAxes: [{
          time: {
            unit: 'date'
          },
          gridLines: {
            display: false
          },
          ticks: {
            maxTicksLimit: 7
          }
        }],
        yAxes: [{
          ticks: {
            min: 0,
            max: 100,
            callback: function(value) {
              const valoresPermitidos = [0, 10, 50, 100];
              return valoresPermitidos.includes(value) ? value : '';
            }
          },
          gridLines: {
            color: "rgba(0, 0, 0, .125)",
          }
        }],
      },
      legend: {
        display: false
      }
    }
  });

}


function graficoFinanca(result) {

  Chart.defaults.global.defaultFontFamily =
    '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
  Chart.defaults.global.defaultFontColor = '#292b2c';

  var ctx_financas = document.getElementById("graficoFinanceiro");

  var myLineChart_financas = new Chart(ctx_financas, {
    type: 'bar',
    data: {
      labels: result.financas_meses,
      datasets: [{
        label: "Entrada",
        backgroundColor: "rgba(2,117,216,1)",
        borderColor: "rgba(2,117,216,1)",
        data: result.financas_valores, // sempre números
      }],
    },
    options: {
      scales: {
        xAxes: [{
          gridLines: {
            display: false
          },
          ticks: {
            maxTicksLimit: 6
          }
        }],
        yAxes: [{
          ticks: {
            min: 0,
            maxTicksLimit: 5,
            callback: function(value) {
              return value.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
              });
            }
          },
          gridLines: {
            display: true
          }
        }],
      },

      legend: {
        display: false
      },

      // 🔹 TOOLTIP FORMATADO
      tooltips: {
        callbacks: {
          label: function(tooltipItem, data) {
            var value = tooltipItem.yLabel || 0;
            return value.toLocaleString('pt-BR', {
              style: 'currency',
              currency: 'BRL'
            });
          }
        }
      }
    }
  });
}

function graficoTopVendas(result){

  Chart.defaults.global.defaultFontFamily =
    '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
  Chart.defaults.global.defaultFontColor = '#292b2c';

  var ctx = document.getElementById("myPieChart");

  var myPieChart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: result.top_produtos,
      datasets: [{
        data: result.top_porcentagem,
        backgroundColor: result.top_cores,
      }],
    },
    options: {
      tooltips: {
        callbacks: {
          label: function(tooltipItem, data) {
            var label = data.labels[tooltipItem.index] || '';
            var value = data.datasets[0].data[tooltipItem.index] || 0;
            return label + ': ' + value + '%';
          }
        }
      }
    }
  });
}
