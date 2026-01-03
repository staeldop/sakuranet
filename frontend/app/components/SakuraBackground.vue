<script setup lang="ts">
import { onMounted, ref } from 'vue'

interface Petal {
  id: number
  style: {
    left: string
    animationDuration: string
    animationDelay: string
    width: string
    height: string
    opacity: number
  }
}

const petals = ref<Petal[]>([])
const PETAL_COUNT = 30 

onMounted(() => {
  const newPetals: Petal[] = []
  
  for (let i = 0; i < PETAL_COUNT; i++) {
    // Увеличили разброс времени, чтобы на длинных страницах они падали не синхронно
    const duration = Math.random() * 10 + 10 + 's' 
    const delay = Math.random() * 5 + 's' 
    const size = Math.random() * 10 + 10 + 'px' 
    
    newPetals.push({
      id: i,
      style: {
        left: Math.random() * 100 + '%', 
        animationDuration: duration,
        animationDelay: delay,
        width: size,
        height: size,
        opacity: Math.random() * 0.4 + 0.2 
      }
    })
  }
  
  petals.value = newPetals
})
</script>

<template>
  <div class="sakura-container">
    <span 
      v-for="petal in petals" 
      :key="petal.id" 
      class="petal" 
      :style="petal.style"
    ></span>
  </div>
</template>

<style scoped>
.sakura-container {
  /* 🔥 ВАЖНО: absolute привязывает блок к родителю (всей странице), а не к экрану */
  position: absolute; 
  top: 0;
  left: 0;
  width: 100%;
  height: 100%; /* Растягиваемся на всю высоту родителя */
  
  pointer-events: none;
  z-index: 1; /* Уровень слоя (будет регулироваться в layout) */
  overflow: hidden;
}

.petal {
  position: absolute;
  top: -50px; 
  background: linear-gradient(120deg, #ffc0cb, #ffb7b2); 
  border-radius: 100% 0 100% 0;
  animation: fall linear infinite;
}

@keyframes fall {
  0% {
    top: -5%;
    transform: translateX(0) rotate(0deg);
    opacity: 0;
  }
  20% {
    opacity: 1; 
  }
  100% {
    /* Падаем до самого низа длинной страницы */
    top: 100%; 
    transform: translateX(100px) rotate(720deg); 
    opacity: 0;
  }
}
</style>