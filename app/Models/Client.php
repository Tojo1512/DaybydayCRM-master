<?php
namespace App\Models;

use App\Http\Controllers\ClientsController;
use App\Observers\ElasticSearchObserver;
use App\Traits\SearchableTrait;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed user_id
 * @property mixed company_name
 * @property mixed vat
 * @property mixed id
 */
class Client extends Model
{
    use  SearchableTrait, SoftDeletes;

    protected $searchableFields = ['company_name', 'vat', 'address'];

    protected $fillable = [
        'external_id',
        'name',
        'company_name',
        'vat',
        'email',
        'address',
        'zipcode',
        'city',
        'primary_number',
        'secondary_number',
        'industry_id',
        'company_type',
        'user_id',
        'client_number'];

    public static function boot()
    {
        parent::boot();
        // This makes it easy to toggle the search feature flag
        // on and off. This is going to prove useful later on
        // when deploy the new search engine to a live app.
        //if (config('services.search.enabled')) {
        static::observe(ElasticSearchObserver::class);
        //}
    }

    public function updateAssignee(User $user)
    {
        $this->user_id = $user->id;
        $this->save();

        event(new \App\Events\ClientAction($this, ClientsController::UPDATED_ASSIGN));
    }

    public function displayValue()
    {
        return $this->company_name;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'client_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'client_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'source');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function primaryContact()
    {
        return $this->contacts()->where('is_primary', 1);
    }

    public function getprimaryContactAttribute()
    {
        return $this->hasMany(Contact::class)->whereIsPrimary(true)->first();
    }

    public function getAssignedUserAttribute()
    {
        return User::findOrFail($this->user_id);
    }

    public static function whereExternalId($external_id)
    {
        return self::where('external_id', $external_id)->first();
    }

    /**
     * @return array
     */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }

    /**
     * Renvoie la relation associée à ce client.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function getRelated()
    {
        // Renvoyer une relation au lieu de l'instance elle-même
        return $this->contacts();
    }
    
    /**
     * Crée une requête pour vérifier l'existence d'une relation.
     * Cette méthode est requise pour les requêtes "whereHas" d'Eloquent.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|string $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(\Illuminate\Database\Eloquent\Builder $query, \Illuminate\Database\Eloquent\Builder $parentQuery, $columns = ['*'])
    {
        return $query->select($columns)->whereColumn(
            $this->getQualifiedKeyName(), '=', $parentQuery->getModel()->getTable().'.'.$this->getForeignKey()
        );
    }
    
    /**
     * Crée une requête pour compter l'existence d'une relation.
     * Cette méthode est requise pour les requêtes "has" d'Eloquent avec un opérateur de comparaison.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceCountQuery(\Illuminate\Database\Eloquent\Builder $query, \Illuminate\Database\Eloquent\Builder $parentQuery)
    {
        return $this->getRelationExistenceQuery(
            $query, $parentQuery, new \Illuminate\Database\Query\Expression('count(*)')
        );
    }
    
    /**
     * Crée une nouvelle instance de requête sans charger les relations.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutRelationships()
    {
        return $this->newQuery();
    }
}
