// ===== ADVANCED ANIMATIONS - GSAP & THREE.JS =====

// ===== GSAP ADVANCED ANIMATIONS =====
class AnimationController {
    constructor() {
        this.isGSAPLoaded = typeof gsap !== 'undefined';
        this.isScrollTriggerLoaded = typeof ScrollTrigger !== 'undefined';
        
        if (this.isGSAPLoaded) {
            this.init();
        }
    }
    
    init() {
        if (this.isScrollTriggerLoaded) {
            gsap.registerPlugin(ScrollTrigger);
        }
        
        this.initHeroAnimations();
        this.initScrollAnimations();
        this.initInteractiveAnimations();
        this.initTextAnimations();
        this.initMorphingAnimations();
        this.initParticleSystem();
    }
    
    // ===== HERO SECTION ANIMATIONS =====
    initHeroAnimations() {
        // Create master timeline
        const masterTL = gsap.timeline();
        
        // Loading to hero transition
        masterTL.fromTo('.hero-section', {
            opacity: 0,
            scale: 1.1
        }, {
            opacity: 1,
            scale: 1,
            duration: 1.5,
            ease: 'power3.out'
        });
        
        // Text reveal animations
        this.createTextRevealAnimation();
        
        // Device mockup animation
        this.animateDeviceMockup();
        
        // Floating elements
        this.animateFloatingElements();
        
        // Stats counter animation
        this.animateStatsCounter();
    }
    
    createTextRevealAnimation() {
        // Split text for character-by-character animation
        const titleHighlight = document.querySelector('.title-highlight');
        const titleMain = document.querySelector('.title-main');
        
        if (titleHighlight) {
            const chars = this.splitText(titleHighlight);
            gsap.fromTo(chars, {
                y: 100,
                opacity: 0,
                rotationX: 90
            }, {
                y: 0,
                opacity: 1,
                rotationX: 0,
                duration: 0.8,
                stagger: 0.05,
                ease: 'power3.out',
                delay: 0.5
            });
        }
        
        if (titleMain) {
            gsap.fromTo(titleMain, {
                y: 50,
                opacity: 0,
                scale: 0.9
            }, {
                y: 0,
                opacity: 1,
                scale: 1,
                duration: 1,
                ease: 'power3.out',
                delay: 1
            });
        }
        
        // Subtitle typewriter effect
        this.createTypewriterEffect('.hero-subtitle');
    }
    
    splitText(element) {
        const text = element.textContent;
        element.innerHTML = '';
        
        return text.split('').map(char => {
            const span = document.createElement('span');
            span.textContent = char === ' ' ? '\u00A0' : char;
            span.style.display = 'inline-block';
            element.appendChild(span);
            return span;
        });
    }
    
    createTypewriterEffect(selector) {
        const element = document.querySelector(selector);
        if (!element) return;
        
        const text = element.textContent;
        element.textContent = '';
        element.style.opacity = 1;
        
        gsap.to({}, {
            duration: text.length * 0.05,
            ease: 'none',
            delay: 1.5,
            onUpdate: function() {
                const progress = this.progress();
                const currentLength = Math.floor(progress * text.length);
                element.textContent = text.slice(0, currentLength);
            }
        });
    }
    
    animateDeviceMockup() {
        const device = document.querySelector('.device-mockup');
        if (!device) return;
        
        // Initial setup
        gsap.set(device, {
            scale: 0.5,
            rotationY: 45,
            rotationX: 15,
            opacity: 0
        });
        
        // Main animation
        gsap.to(device, {
            scale: 1,
            rotationY: 0,
            rotationX: 0,
            opacity: 1,
            duration: 2,
            ease: 'power3.out',
            delay: 2
        });
        
        // Glow effect
        const deviceGlow = device.querySelector('.device-glow');
        if (deviceGlow) {
            gsap.to(deviceGlow, {
                scale: 1.2,
                opacity: 0.6,
                duration: 2,
                repeat: -1,
                yoyo: true,
                ease: 'power2.inOut'
            });
        }
        
        // Screen content animation
        this.animateScreenContent();
    }
    
    animateScreenContent() {
        const demoHeader = document.querySelector('.demo-header');
        const chartBars = document.querySelectorAll('.bar');
        
        if (demoHeader) {
            gsap.fromTo(demoHeader, {
                y: -20,
                opacity: 0
            }, {
                y: 0,
                opacity: 1,
                duration: 1,
                delay: 3
            });
        }
        
        if (chartBars.length) {
            chartBars.forEach((bar, index) => {
                const height = bar.style.height;
                gsap.fromTo(bar, {
                    height: '0%'
                }, {
                    height: height,
                    duration: 1,
                    delay: 3.5 + (index * 0.1),
                    ease: 'power2.out'
                });
            });
        }
    }
    
    animateFloatingElements() {
        const floatingCards = document.querySelectorAll('.floating-card');
        
        floatingCards.forEach((card, index) => {
            // Initial animation
            gsap.fromTo(card, {
                scale: 0,
                rotation: 180,
                opacity: 0
            }, {
                scale: 1,
                rotation: 0,
                opacity: 1,
                duration: 1,
                delay: 2.5 + (index * 0.3),
                ease: 'power3.out'
            });
            
            // Continuous floating animation
            gsap.to(card, {
                y: -20,
                duration: 2 + Math.random(),
                repeat: -1,
                yoyo: true,
                ease: 'power2.inOut',
                delay: index * 0.5
            });
            
            // Hover effect
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    scale: 1.1,
                    rotation: 5,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    scale: 1,
                    rotation: 0,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
        });
    }
    
    animateStatsCounter() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        statNumbers.forEach(stat => {
            const target = parseInt(stat.dataset.count);
            
            gsap.fromTo({value: 0}, {
                value: target,
                duration: 2,
                delay: 3,
                ease: 'power2.out',
                onUpdate: function() {
                    stat.textContent = Math.floor(this.targets()[0].value);
                }
            });
        });
    }
    
    // ===== SCROLL ANIMATIONS =====
    initScrollAnimations() {
        if (!this.isScrollTriggerLoaded) return;
        
        // Parallax backgrounds
        this.initParallaxEffects();
        
        // Section reveals
        this.initSectionReveals();
        
        // Feature cards cascade
        this.initFeatureCardsCascade();
        
        // Service items alternating
        this.initServiceItemsAnimation();
        
        // Contact form reveal
        this.initContactFormAnimation();
    }
    
    initParallaxEffects() {
        const shapes = document.querySelectorAll('.shape');
        
        shapes.forEach((shape, index) => {
            gsap.to(shape, {
                y: -100 * (index + 1),
                scrollTrigger: {
                    trigger: shape,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 1
                }
            });
        });
        
        // Navbar background opacity
        gsap.to('.navbar', {
            backgroundColor: 'rgba(13, 17, 23, 0.95)',
            scrollTrigger: {
                trigger: 'body',
                start: 'top -80px',
                end: 'bottom bottom',
                toggleActions: 'play none none reverse'
            }
        });
    }
    
    initSectionReveals() {
        const sections = document.querySelectorAll('section');
        
        sections.forEach(section => {
            gsap.fromTo(section, {
                opacity: 0,
                y: 50
            }, {
                opacity: 1,
                y: 0,
                duration: 1,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: section,
                    start: 'top 80%',
                    once: true
                }
            });
        });
    }
    
    initFeatureCardsCascade() {
        const featureCards = document.querySelectorAll('.feature-card');
        
        gsap.fromTo(featureCards, {
            y: 100,
            opacity: 0,
            scale: 0.8
        }, {
            y: 0,
            opacity: 1,
            scale: 1,
            duration: 0.8,
            stagger: 0.2,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.features-grid',
                start: 'top 80%',
                once: true
            }
        });
        
        // Individual hover animations
        featureCards.forEach(card => {
            const icon = card.querySelector('.feature-icon');
            const title = card.querySelector('.feature-title');
            
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    y: -10,
                    scale: 1.05,
                    duration: 0.3,
                    ease: 'power2.out'
                });
                
                if (icon) {
                    gsap.to(icon, {
                        rotation: 360,
                        scale: 1.1,
                        duration: 0.5,
                        ease: 'power2.out'
                    });
                }
                
                if (title) {
                    gsap.to(title, {
                        color: '#00d4ff',
                        duration: 0.3
                    });
                }
            });
            
            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    y: 0,
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
                
                if (icon) {
                    gsap.to(icon, {
                        rotation: 0,
                        scale: 1,
                        duration: 0.5,
                        ease: 'power2.out'
                    });
                }
                
                if (title) {
                    gsap.to(title, {
                        color: '#ffffff',
                        duration: 0.3
                    });
                }
            });
        });
    }
    
    initServiceItemsAnimation() {
        const serviceItems = document.querySelectorAll('.service-item');
        
        serviceItems.forEach((item, index) => {
            const isEven = index % 2 === 0;
            
            gsap.fromTo(item, {
                x: isEven ? -100 : 100,
                opacity: 0,
                rotation: isEven ? -5 : 5
            }, {
                x: 0,
                opacity: 1,
                rotation: 0,
                duration: 1,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: item,
                    start: 'top 80%',
                    once: true
                }
            });
        });
    }
    
    initContactFormAnimation() {
        const formGroups = document.querySelectorAll('.form-group');
        
        gsap.fromTo(formGroups, {
            y: 50,
            opacity: 0
        }, {
            y: 0,
            opacity: 1,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.contact-form',
                start: 'top 80%',
                once: true
            }
        });
    }
    
    // ===== INTERACTIVE ANIMATIONS =====
    initInteractiveAnimations() {
        this.initButtonAnimations();
        this.initNavAnimations();
        this.initDemoControls();
    }
    
    initButtonAnimations() {
        const buttons = document.querySelectorAll('.btn');
        
        buttons.forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                gsap.to(btn, {
                    scale: 1.05,
                    y: -3,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            btn.addEventListener('mouseleave', () => {
                gsap.to(btn, {
                    scale: 1,
                    y: 0,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            btn.addEventListener('click', () => {
                gsap.to(btn, {
                    scale: 0.95,
                    duration: 0.1,
                    yoyo: true,
                    repeat: 1,
                    ease: 'power2.inOut'
                });
            });
        });
    }
    
    initNavAnimations() {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', () => {
                gsap.to(link, {
                    scale: 1.05,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            link.addEventListener('mouseleave', () => {
                gsap.to(link, {
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
        });
    }
    
    initDemoControls() {
        const demoBtns = document.querySelectorAll('.demo-btn');
        const demoVideo = document.querySelector('.demo-video');
        
        demoBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active state from all buttons
                demoBtns.forEach(b => {
                    gsap.to(b, {
                        scale: 1,
                        backgroundColor: 'rgba(255, 255, 255, 0.05)',
                        duration: 0.3
                    });
                });
                
                // Activate clicked button
                gsap.to(btn, {
                    scale: 1.1,
                    backgroundColor: 'rgba(0, 212, 255, 0.2)',
                    duration: 0.3
                });
                
                // Animate video transition
                if (demoVideo) {
                    gsap.to(demoVideo, {
                        scale: 0.9,
                        opacity: 0.5,
                        duration: 0.3,
                        onComplete: () => {
                            gsap.to(demoVideo, {
                                scale: 1,
                                opacity: 1,
                                duration: 0.3
                            });
                        }
                    });
                }
            });
        });
    }
    
    // ===== TEXT ANIMATIONS =====
    initTextAnimations() {
        this.initSectionTitleAnimations();
        this.initTextRevealOnScroll();
    }
    
    initSectionTitleAnimations() {
        const sectionTitles = document.querySelectorAll('.section-title');
        
        sectionTitles.forEach(title => {
            gsap.fromTo(title, {
                y: 50,
                opacity: 0
            }, {
                y: 0,
                opacity: 1,
                duration: 1,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: title,
                    start: 'top 80%',
                    once: true
                }
            });
        });
    }
    
    initTextRevealOnScroll() {
        const paragraphs = document.querySelectorAll('.section-subtitle, .feature-description, .service-description');
        
        paragraphs.forEach(p => {
            gsap.fromTo(p, {
                y: 30,
                opacity: 0
            }, {
                y: 0,
                opacity: 1,
                duration: 0.8,
                delay: 0.3,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: p,
                    start: 'top 85%',
                    once: true
                }
            });
        });
    }
    
    // ===== MORPHING ANIMATIONS =====
    initMorphingAnimations() {
        this.createMorphingShapes();
        this.initIconMorphing();
    }
    
    createMorphingShapes() {
        const shapes = document.querySelectorAll('.shape');
        
        shapes.forEach(shape => {
            gsap.to(shape, {
                morphSVG: {
                    shape: "M100,200 C100,100 200,100 200,200 C200,300 100,300 100,200"
                },
                duration: 4,
                repeat: -1,
                yoyo: true,
                ease: 'power2.inOut'
            });
        });
    }
    
    initIconMorphing() {
        const icons = document.querySelectorAll('.feature-icon i, .contact-icon i');
        
        icons.forEach(icon => {
            icon.addEventListener('mouseenter', () => {
                gsap.to(icon, {
                    rotation: 360,
                    scale: 1.2,
                    duration: 0.6,
                    ease: 'power2.out'
                });
            });
            
            icon.addEventListener('mouseleave', () => {
                gsap.to(icon, {
                    rotation: 0,
                    scale: 1,
                    duration: 0.6,
                    ease: 'power2.out'
                });
            });
        });
    }
    
    // ===== PARTICLE SYSTEM =====
    initParticleSystem() {
        this.createFloatingParticles();
        this.createInteractiveParticles();
    }
    
    createFloatingParticles() {
        const particleContainers = document.querySelectorAll('.particles-bg');
        
        particleContainers.forEach(container => {
            for (let i = 0; i < 30; i++) {
                this.createParticle(container);
            }
        });
    }
    
    createParticle(container) {
        const particle = document.createElement('div');
        particle.className = 'dynamic-particle';
        
        // Random properties
        const size = Math.random() * 8 + 2;
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        
        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: radial-gradient(circle, #00d4ff, transparent);
            border-radius: 50%;
            left: ${x}%;
            top: ${y}%;
            pointer-events: none;
            opacity: 0.6;
        `;
        
        container.appendChild(particle);
        
        // Animate particle
        gsap.to(particle, {
            y: -200,
            x: Math.random() * 200 - 100,
            opacity: 0,
            duration: Math.random() * 5 + 3,
            repeat: -1,
            delay: Math.random() * 5,
            ease: 'power1.out',
            onRepeat: () => {
                gsap.set(particle, {
                    y: 0,
                    x: Math.random() * 100 + '%',
                    opacity: 0.6
                });
            }
        });
    }
    
    createInteractiveParticles() {
        document.addEventListener('mousemove', (e) => {
            if (Math.random() > 0.95) { // Only create particles occasionally
                this.createMouseParticle(e.clientX, e.clientY);
            }
        });
    }
    
    createMouseParticle(x, y) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: fixed;
            width: 4px;
            height: 4px;
            background: #00d4ff;
            border-radius: 50%;
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
            z-index: 9999;
        `;
        
        document.body.appendChild(particle);
        
        gsap.to(particle, {
            y: -50,
            x: Math.random() * 100 - 50,
            opacity: 0,
            scale: 0,
            duration: 1,
            ease: 'power2.out',
            onComplete: () => {
                particle.remove();
            }
        });
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    new AnimationController();
});

// ===== EXPORT FOR MODULE USE =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AnimationController;
}

