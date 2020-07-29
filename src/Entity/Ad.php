<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Comment;
use App\Repository\AdRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *      collectionOperations={
 *          "get",
 *          "post"={"security"="is_granted('ROLE_USER')"}
 *      },
 *      itemOperations={
 *          "get",
 *          "put"={"security"="is_granted('ROLE_ADMIN') or object.getAuthor() == user"},
 *          "delete"={"security"="is_granted('ROLE_ADMIN') or object.getAuthor() == user"}
 *      },
 *      attributes={
 *          "pagination_enabled"=false,
 *          "pagination_items_per_page"=20
 *      },
 *      normalizationContext={"groups"={"ad:read"}},
 *      denormalizationContext={"groups"={"ad:write"}}
 * )
 * 
 * @ORM\Entity(repositoryClass=AdRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Ad
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"ad:read", "ad:write"})
     */
    private $title;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"ad:read", "ad:write"})
     */
    private $price;

    /**
     * @ORM\Column(type="text")
     * @Groups({"ad:read", "ad:write"})
     */
    private $introduction;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"ad:read", "ad:write"})
     */
    private $rooms;

    /**
     * @ORM\Column(type="text")
     * @Groups({"ad:read", "ad:write"})
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="ads")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("ad:read")
     */
    private $author;

    /**
     * @ORM\OneToMany(targetEntity=Booking::class, cascade={"remove"}, mappedBy="ad")
     * @Groups("ad:read")
     */
    private $bookings;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, cascade={"remove"}, mappedBy="ad")
     * @Groups("ad:read")
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity=Image::class, cascade={"persist", "remove"}, mappedBy="ad")
     * @Groups({"ad:read", "ad:write"})
     */
    private $images;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    /**
     * Permet de récupérer le commentaire d'un auteur par rapport à une annonce.
     *
     * @param User $author
     * @return Comment|null
     */
    public function getCommentFromAuthor(User $author)
    {
        foreach ($this->comments as $comment) {
            if ($comment->getAuthor() === $author) return $comment;
        }

        return null;
    }

    /**
     * Permet d'obtenir la moyenne globale des notes pour cette annonce.
     *
     * @return float
     */
    public function getAvgRatings()
    {
        // Calculer la somme des notations.
        $sum = array_reduce($this->comments->toArray(), function ($total, $comment) {
            return $total + $comment->getRating();
        }, 0);

        // Faire la division pour avoir la moyenne.
        if (count($this->comments) > 0) return $sum / count($this->comments);

        return 0;
    }

    /**
     * Permet d'obtenir un tableau des jours qui ne sont pas disponibles pour cette annonce.
     *
     * @return array Un tableau d'objets DateTime représentant les jours d'occupation.
     */
    public function getNotAvailableDays()
    {
        $notAvailableDays = [];

        foreach ($this->bookings as $booking) {
            // Calculer les jours qui se trouvent entre la date d'arrivée et de départ.
            $resultat = range(
                $booking->getStartDate()->getTimestamp(),
                $booking->getEndDate()->getTimestamp(),
                24 * 60 * 60
            );

            $days = array_map(function ($dayTimestamp) {
                return new \DateTime(date('Y-m-d', $dayTimestamp));
            }, $resultat);

            $notAvailableDays = array_merge($notAvailableDays, $days);
        }

        return $notAvailableDays;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(string $introduction): self
    {
        $this->introduction = $introduction;

        return $this;
    }

    public function getRooms(): ?int
    {
        return $this->rooms;
    }

    public function setRooms(int $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection|Booking[]
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings[] = $booking;
            $booking->setAd($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->contains($booking)) {
            $this->bookings->removeElement($booking);
            // set the owning side to null (unless already changed)
            if ($booking->getAd() === $this) {
                $booking->setAd(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setAd($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getAd() === $this) {
                $comment->setAd(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Image[]
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setAd($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->contains($image)) {
            $this->images->removeElement($image);
            // set the owning side to null (unless already changed)
            if ($image->getAd() === $this) {
                $image->setAd(null);
            }
        }

        return $this;
    }
}
