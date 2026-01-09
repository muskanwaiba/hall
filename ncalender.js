let curr = new Date();
let booked = [];

function loadCalendar() {
  fetch(`fetch_bookings.php?month=${curr.getFullYear()}-${String(curr.getMonth()+1).padStart(2,'0')}`)
    .then(r=>r.json())
    .then(d=>{
      booked=d;
      render();
    });
}

function render() {
  const table=document.getElementById('calendarTable');
  let html="<tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr><tr>";

  let first=(new Date(curr.getFullYear(),curr.getMonth(),1).getDay()||7)-1;
  for(let i=0;i<first;i++) html+="<td></td>";

  let days=new Date(curr.getFullYear(),curr.getMonth()+1,0).getDate();
  for(let d=1;d<=days;d++){
    let date=`${curr.getFullYear()}-${String(curr.getMonth()+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    let cls=booked.includes(date)?"booked":"";
    html+=`<td class="${cls}">${d}</td>`;
    if((d+first)%7===0) html+="</tr><tr>";
  }
  table.innerHTML=html;
  document.getElementById("monthYear").textContent=
    curr.toLocaleString('default',{month:'long',year:'numeric'});
}

document.getElementById("prevMonth").onclick=()=>{curr.setMonth(curr.getMonth()-1);loadCalendar();}
document.getElementById("nextMonth").onclick=()=>{curr.setMonth(curr.getMonth()+1);loadCalendar();}
loadCalendar();
