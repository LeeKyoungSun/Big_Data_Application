<?php
session_start();
include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Baseball Analytics</title>
    <link rel="stylesheet" href="css/main.css"/>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px;}
        .wrap{ 
            width: 80%;
            height:100vh;
            display:flex;
            flex-direction:column;
        }
        .stage{flex:1;position:relative;overflow:hidden;}

        .mainflex{
            display:flex ;
            margin-top: 20px;
        }
        .img-box{
            position:absolute;
            top:0;left:0;
            width:100%;
            height:80%;
            padding: 0 20px;
            border-left: white 1px solid;
            display:flex;
            justify-content:center;
            align-items:center;opacity:0;
            transition:opacity 0.8s ease;
        }
        .img-box.active{opacity:1;}
        .img-box img{max-width:100%;max-height:100%;object-fit:contain;}

        .info{position:absolute;bottom:12px;right:16px;font-size:14px;color:#ddd;background:rgba(0,0,0,0.4);padding:4px 10px;border-radius:6px}
    </style>
</head>
<body>
    <div class="layout">
    <!-- 로그인/회원가입  -->
        <?php
        include 'pages/nav.php';
        ?>
    <div class="mainflex">
        <div>
            <h1>Baseball Analytics</h1>
            <div style="font-size: 20px;line-height:1.7">Get your own analysis with baseball data. <br/>
                You can get the analysis by team, player, and league/game.<br/>
                There are examples of what you can do. <br/>
            </div>
        </div>
        <div class="wrap">
            <div class="stage" id="stage">
            <!-- <button id="prev" class="btn">◀</button>
            <button id="next" class="btn">▶</button> -->
            <div class="info" id="info">0 / 0</div>
            </div>
        </div>
    </div>
    </div>
    <script>
        // 설정: images 폴더 경로와 manifest 파일명
        const IMAGES_PATH = 'images/';

        const stage = document.getElementById('stage');
        const info = document.getElementById('info');
        // const prev = document.getElementById('prev');
        // const next = document.getElementById('next');

        let imgs = ["image 19.png", "image 20.png","image 22.png","image 23.png","image 17.png", "image 18.png" ];
        let current = 0;
        let timer = null;
        const INTERVAL = 4000; // 4초마다 자동 전환

        
        function buildSlides(){
            stage.querySelectorAll('.img-box').forEach(n=>n.remove());


            imgs.forEach((file, idx)=>{
            const box = document.createElement('div');
            box.className = 'img-box';
            box.dataset.index = idx;

            const img = document.createElement('img');
            img.src = IMAGES_PATH + file;
            img.alt = file;

            box.appendChild(img);
            stage.appendChild(box);
            });
            show(0);
        }


        function show(i){
            current = (i + imgs.length) % imgs.length;
            stage.querySelectorAll('.img-box').forEach(n => n.classList.remove('active'));
            const now = stage.querySelector(`.img-box[data-index="${current}"]`);
            if(now) now.classList.add('active');
            info.textContent = `${current+1} / ${imgs.length}`;
        }


        function nextSlide(){ show(current+1); }
        function prevSlide(){ show(current-1); }

        function startAutoSlide(){ stopAutoSlide(); timer = setInterval(nextSlide, INTERVAL); }
        function stopAutoSlide(){ if(timer) clearInterval(timer); }
        buildSlides();
        startAutoSlide();
    </script>
   
   <?php
        $conn->close();
    ?>
</body>
</div>
</html>
