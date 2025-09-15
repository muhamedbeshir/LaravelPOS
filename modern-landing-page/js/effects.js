// ===== ADVANCED VISUAL EFFECTS - THREE.JS & WEBGL =====

class EffectsController {
    constructor() {
        this.isThreeLoaded = typeof THREE !== 'undefined';
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.particles = null;
        this.mouse = { x: 0, y: 0 };
        
        this.init();
    }
    
    init() {
        this.initWebGLBackground();
        this.initGlowEffects();
        this.initHolographicEffects();
        this.initNeuralNetworkBackground();
        this.initMatrixRain();
        this.initAudioVisualizer();
        this.initMagneticEffect();
        this.initLiquidDistortion();
    }
    
    // ===== THREE.JS BACKGROUND =====
    initWebGLBackground() {
        if (!this.isThreeLoaded) return;
        
        // Create container for WebGL
        const container = document.createElement('div');
        container.id = 'webgl-background';
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.7;
        `;
        document.body.appendChild(container);
        
        // Scene setup
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.renderer = new THREE.WebGLRenderer({ alpha: true });
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        container.appendChild(this.renderer.domElement);
        
        // Create animated particles
        this.createThreeParticles();
        
        // Create floating geometries
        this.createFloatingGeometries();
        
        // Animation loop
        this.animate();
        
        // Mouse interaction
        this.initMouseInteraction();
        
        // Resize handler
        window.addEventListener('resize', () => this.onWindowResize());
    }
    
    createThreeParticles() {
        const geometry = new THREE.BufferGeometry();
        const particleCount = 1000;
        
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);
        const velocities = new Float32Array(particleCount * 3);
        
        for (let i = 0; i < particleCount; i++) {
            const i3 = i * 3;
            
            // Position
            positions[i3] = (Math.random() - 0.5) * 100;
            positions[i3 + 1] = (Math.random() - 0.5) * 100;
            positions[i3 + 2] = (Math.random() - 0.5) * 100;
            
            // Color
            const color = new THREE.Color();
            color.setHSL(Math.random() * 0.3 + 0.5, 1, 0.5);
            colors[i3] = color.r;
            colors[i3 + 1] = color.g;
            colors[i3 + 2] = color.b;
            
            // Velocity
            velocities[i3] = (Math.random() - 0.5) * 0.02;
            velocities[i3 + 1] = (Math.random() - 0.5) * 0.02;
            velocities[i3 + 2] = (Math.random() - 0.5) * 0.02;
        }
        
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        geometry.setAttribute('velocity', new THREE.BufferAttribute(velocities, 3));
        
        const material = new THREE.PointsMaterial({
            size: 2,
            vertexColors: true,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending
        });
        
        this.particles = new THREE.Points(geometry, material);
        this.scene.add(this.particles);
    }
    
    createFloatingGeometries() {
        const geometries = [
            new THREE.TetrahedronGeometry(2),
            new THREE.OctahedronGeometry(2),
            new THREE.IcosahedronGeometry(2)
        ];
        
        for (let i = 0; i < 10; i++) {
            const geometry = geometries[Math.floor(Math.random() * geometries.length)];
            const material = new THREE.MeshBasicMaterial({
                color: new THREE.Color().setHSL(Math.random() * 0.3 + 0.5, 1, 0.5),
                wireframe: true,
                transparent: true,
                opacity: 0.3
            });
            
            const mesh = new THREE.Mesh(geometry, material);
            mesh.position.set(
                (Math.random() - 0.5) * 50,
                (Math.random() - 0.5) * 50,
                (Math.random() - 0.5) * 50
            );
            
            mesh.userData = {
                rotationSpeed: {
                    x: (Math.random() - 0.5) * 0.02,
                    y: (Math.random() - 0.5) * 0.02,
                    z: (Math.random() - 0.5) * 0.02
                }
            };
            
            this.scene.add(mesh);
        }
    }
    
    animate() {
        requestAnimationFrame(() => this.animate());
        
        // Rotate particles
        if (this.particles) {
            this.particles.rotation.y += 0.001;
            
            // Update particle positions
            const positions = this.particles.geometry.attributes.position.array;
            const velocities = this.particles.geometry.attributes.velocity.array;
            
            for (let i = 0; i < positions.length; i += 3) {
                positions[i] += velocities[i];
                positions[i + 1] += velocities[i + 1];
                positions[i + 2] += velocities[i + 2];
                
                // Boundary check
                if (Math.abs(positions[i]) > 50) velocities[i] *= -1;
                if (Math.abs(positions[i + 1]) > 50) velocities[i + 1] *= -1;
                if (Math.abs(positions[i + 2]) > 50) velocities[i + 2] *= -1;
            }
            
            this.particles.geometry.attributes.position.needsUpdate = true;
        }
        
        // Rotate geometries
        this.scene.children.forEach(child => {
            if (child.userData.rotationSpeed) {
                child.rotation.x += child.userData.rotationSpeed.x;
                child.rotation.y += child.userData.rotationSpeed.y;
                child.rotation.z += child.userData.rotationSpeed.z;
            }
        });
        
        // Mouse interaction
        this.camera.position.x += (this.mouse.x * 5 - this.camera.position.x) * 0.05;
        this.camera.position.y += (-this.mouse.y * 5 - this.camera.position.y) * 0.05;
        this.camera.lookAt(this.scene.position);
        
        this.renderer.render(this.scene, this.camera);
    }
    
    initMouseInteraction() {
        document.addEventListener('mousemove', (event) => {
            this.mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
            this.mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
        });
    }
    
    onWindowResize() {
        this.camera.aspect = window.innerWidth / window.innerHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(window.innerWidth, window.innerHeight);
    }
    
    // ===== GLOW EFFECTS =====
    initGlowEffects() {
        this.createTextGlow();
        this.createElementGlow();
        this.createHoverGlow();
    }
    
    createTextGlow() {
        const glowElements = document.querySelectorAll('.animate-textGlow');
        
        glowElements.forEach(element => {
            element.style.textShadow = `
                0 0 10px #00d4ff,
                0 0 20px #00d4ff,
                0 0 30px #00d4ff,
                0 0 40px #00d4ff
            `;
            
            // Animated glow intensity
            setInterval(() => {
                const intensity = 10 + Math.sin(Date.now() * 0.005) * 10;
                element.style.textShadow = `
                    0 0 ${intensity}px #00d4ff,
                    0 0 ${intensity * 2}px #00d4ff,
                    0 0 ${intensity * 3}px #00d4ff,
                    0 0 ${intensity * 4}px #00d4ff
                `;
            }, 100);
        });
    }
    
    createElementGlow() {
        const glowContainers = document.querySelectorAll('.device-mockup, .feature-card, .btn-primary');
        
        glowContainers.forEach(container => {
            container.addEventListener('mouseenter', () => {
                container.style.filter = 'drop-shadow(0 0 20px rgba(0, 212, 255, 0.5))';
            });
            
            container.addEventListener('mouseleave', () => {
                container.style.filter = 'none';
            });
        });
    }
    
    createHoverGlow() {
        const hoverElements = document.querySelectorAll('.social-link, .nav-link, .demo-btn');
        
        hoverElements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                element.style.boxShadow = '0 0 20px rgba(0, 212, 255, 0.6)';
                element.style.transform = 'scale(1.05)';
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.boxShadow = '';
                element.style.transform = '';
            });
        });
    }
    
    // ===== HOLOGRAPHIC EFFECTS =====
    initHolographicEffects() {
        this.createHolographicCards();
        this.createRainbowBorders();
    }
    
    createHolographicCards() {
        const cards = document.querySelectorAll('.feature-card, .service-item');
        
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                card.style.transform = `
                    perspective(1000px)
                    rotateX(${rotateX}deg)
                    rotateY(${rotateY}deg)
                    scale3d(1.05, 1.05, 1.05)
                `;
                
                // Create holographic reflection
                const gradient = `
                    linear-gradient(
                        ${Math.atan2(y - centerY, x - centerX) * 180 / Math.PI + 90}deg,
                        rgba(255,255,255,0.1) 0%,
                        rgba(0,212,255,0.2) 50%,
                        rgba(108,92,231,0.1) 100%
                    )
                `;
                
                card.style.background = gradient;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
                card.style.background = '';
            });
        });
    }
    
    createRainbowBorders() {
        const elements = document.querySelectorAll('.btn-primary, .contact-form');
        
        elements.forEach(element => {
            element.style.position = 'relative';
            element.style.overflow = 'hidden';
            
            const border = document.createElement('div');
            border.style.cssText = `
                position: absolute;
                top: -2px;
                left: -2px;
                right: -2px;
                bottom: -2px;
                background: linear-gradient(45deg, 
                    #ff0000, #ff7f00, #ffff00, #00ff00, 
                    #0000ff, #4b0082, #9400d3, #ff0000);
                background-size: 400% 400%;
                border-radius: inherit;
                z-index: -1;
                animation: rainbow 3s ease infinite;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            element.appendChild(border);
            
            element.addEventListener('mouseenter', () => {
                border.style.opacity = '0.7';
            });
            
            element.addEventListener('mouseleave', () => {
                border.style.opacity = '0';
            });
        });
        
        // Add rainbow animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rainbow {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // ===== NEURAL NETWORK BACKGROUND =====
    initNeuralNetworkBackground() {
        const canvas = document.createElement('canvas');
        canvas.id = 'neural-network';
        canvas.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            opacity: 0.3;
        `;
        document.body.appendChild(canvas);
        
        const ctx = canvas.getContext('2d');
        let nodes = [];
        let connections = [];
        
        const resizeCanvas = () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            this.initNodes();
        };
        
        const initNodes = () => {
            nodes = [];
            const nodeCount = Math.floor((canvas.width * canvas.height) / 20000);
            
            for (let i = 0; i < nodeCount; i++) {
                nodes.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    radius: Math.random() * 3 + 1
                });
            }
        };
        
        const updateNodes = () => {
            nodes.forEach(node => {
                node.x += node.vx;
                node.y += node.vy;
                
                if (node.x < 0 || node.x > canvas.width) node.vx *= -1;
                if (node.y < 0 || node.y > canvas.height) node.vy *= -1;
            });
        };
        
        const drawConnections = () => {
            ctx.strokeStyle = 'rgba(0, 212, 255, 0.1)';
            ctx.lineWidth = 1;
            
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const dx = nodes[i].x - nodes[j].x;
                    const dy = nodes[i].y - nodes[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    
                    if (distance < 100) {
                        ctx.beginPath();
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.globalAlpha = 1 - distance / 100;
                        ctx.stroke();
                    }
                }
            }
        };
        
        const drawNodes = () => {
            ctx.fillStyle = '#00d4ff';
            nodes.forEach(node => {
                ctx.beginPath();
                ctx.arc(node.x, node.y, node.radius, 0, Math.PI * 2);
                ctx.globalAlpha = 0.8;
                ctx.fill();
            });
        };
        
        const animate = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            updateNodes();
            drawConnections();
            drawNodes();
            requestAnimationFrame(animate);
        };
        
        resizeCanvas();
        animate();
        
        window.addEventListener('resize', resizeCanvas);
    }
    
    // ===== MATRIX RAIN EFFECT =====
    initMatrixRain() {
        if (Math.random() > 0.7) return; // Only show sometimes
        
        const canvas = document.createElement('canvas');
        canvas.id = 'matrix-rain';
        canvas.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            opacity: 0.1;
        `;
        document.body.appendChild(canvas);
        
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        const matrix = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789@#$%^&*()*&^%+-/~{[|`]}";
        const columns = canvas.width / 10;
        const drops = [];
        
        for (let x = 0; x < columns; x++) {
            drops[x] = 1;
        }
        
        const draw = () => {
            ctx.fillStyle = 'rgba(13, 17, 23, 0.04)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#00d4ff';
            ctx.font = '10px monospace';
            
            for (let i = 0; i < drops.length; i++) {
                const text = matrix[Math.floor(Math.random() * matrix.length)];
                ctx.fillText(text, i * 10, drops[i] * 10);
                
                if (drops[i] * 10 > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        };
        
        setInterval(draw, 35);
    }
    
    // ===== AUDIO VISUALIZER =====
    initAudioVisualizer() {
        // Simulated audio visualizer without actual audio
        const canvas = document.createElement('canvas');
        canvas.id = 'audio-visualizer';
        canvas.style.cssText = `
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            z-index: -2;
            opacity: 0.3;
        `;
        document.body.appendChild(canvas);
        
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = 100;
        
        const bars = 64;
        const barWidth = canvas.width / bars;
        
        const draw = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            for (let i = 0; i < bars; i++) {
                const barHeight = Math.random() * canvas.height;
                const hue = (i / bars) * 360;
                
                ctx.fillStyle = `hsla(${hue}, 100%, 50%, 0.8)`;
                ctx.fillRect(i * barWidth, canvas.height - barHeight, barWidth - 2, barHeight);
            }
        };
        
        setInterval(draw, 100);
    }
    
    // ===== MAGNETIC EFFECT =====
    initMagneticEffect() {
        const magneticElements = document.querySelectorAll('.btn, .feature-card, .nav-link');
        
        magneticElements.forEach(element => {
            element.addEventListener('mousemove', (e) => {
                const rect = element.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                const distance = Math.sqrt(x * x + y * y);
                const maxDistance = 100;
                
                if (distance < maxDistance) {
                    const force = (maxDistance - distance) / maxDistance;
                    const moveX = x * force * 0.3;
                    const moveY = y * force * 0.3;
                    
                    element.style.transform = `translate(${moveX}px, ${moveY}px)`;
                }
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.transform = '';
            });
        });
    }
    
    // ===== LIQUID DISTORTION =====
    initLiquidDistortion() {
        const distortionElements = document.querySelectorAll('.hero-visual, .demo-video');
        
        distortionElements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                element.style.filter = 'blur(1px) brightness(1.1) contrast(1.1)';
                element.style.transform = 'scale(1.02)';
                element.style.transition = 'all 0.3s ease';
            });
            
            element.addEventListener('mousemove', (e) => {
                const rect = element.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width;
                const y = (e.clientY - rect.top) / rect.height;
                
                const skewX = (x - 0.5) * 2;
                const skewY = (y - 0.5) * 2;
                
                element.style.transform = `
                    scale(1.02)
                    skew(${skewX}deg, ${skewY}deg)
                `;
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.filter = '';
                element.style.transform = '';
            });
        });
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    new EffectsController();
});

// ===== EXPORT FOR MODULE USE =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EffectsController;
}

